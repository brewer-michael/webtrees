<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Functions;

use Fisharebest\Webtrees\Date;
use Fisharebest\Webtrees\Exceptions\GedcomErrorException;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\GedcomTag;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Note;
use Fisharebest\Webtrees\Place;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Soundex;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\JoinClause;
use PDOException;

/**
 * Class FunctionsImport - common functions
 */
class FunctionsImport
{
    /**
     * Tidy up a gedcom record on import, so that we can access it consistently/efficiently.
     *
     * @param string $rec
     * @param Tree   $tree
     *
     * @return string
     */
    public static function reformatRecord($rec, Tree $tree): string
    {
        // Strip out mac/msdos line endings
        $rec = preg_replace("/[\r\n]+/", "\n", $rec);

        // Extract lines from the record; lines consist of: level + optional xref + tag + optional data
        $num_matches = preg_match_all('/^[ \t]*(\d+)[ \t]*(@[^@]*@)?[ \t]*(\w+)[ \t]?(.*)$/m', $rec, $matches, PREG_SET_ORDER);

        // Process the record line-by-line
        $newrec = '';
        foreach ($matches as $n => $match) {
            [, $level, $xref, $tag, $data] = $match;
            $tag = strtoupper($tag); // Tags should always be upper case
            switch ($tag) {
                // Convert PhpGedView tags to WT
                case '_PGVU':
                    $tag = '_WT_USER';
                    break;
                case '_PGV_OBJS':
                    $tag = '_WT_OBJE_SORT';
                    break;
                // Convert FTM-style "TAG_FORMAL_NAME" into "TAG".
                case 'ABBREVIATION':
                    $tag = 'ABBR';
                    break;
                case 'ADDRESS':
                    $tag = 'ADDR';
                    break;
                case 'ADDRESS1':
                    $tag = 'ADR1';
                    break;
                case 'ADDRESS2':
                    $tag = 'ADR2';
                    break;
                case 'ADDRESS3':
                    $tag = 'ADR3';
                    break;
                case 'ADOPTION':
                    $tag = 'ADOP';
                    break;
                case 'ADULT_CHRISTENING':
                    $tag = 'CHRA';
                    break;
                case 'AFN':
                    // AFN values are upper case
                    $data = strtoupper($data);
                    break;
                case 'AGENCY':
                    $tag = 'AGNC';
                    break;
                case 'ALIAS':
                    $tag = 'ALIA';
                    break;
                case 'ANCESTORS':
                    $tag = 'ANCE';
                    break;
                case 'ANCES_INTEREST':
                    $tag = 'ANCI';
                    break;
                case 'ANNULMENT':
                    $tag = 'ANUL';
                    break;
                case 'ASSOCIATES':
                    $tag = 'ASSO';
                    break;
                case 'AUTHOR':
                    $tag = 'AUTH';
                    break;
                case 'BAPTISM':
                    $tag = 'BAPM';
                    break;
                case 'BAPTISM_LDS':
                    $tag = 'BAPL';
                    break;
                case 'BAR_MITZVAH':
                    $tag = 'BARM';
                    break;
                case 'BAS_MITZVAH':
                    $tag = 'BASM';
                    break;
                case 'BIRTH':
                    $tag = 'BIRT';
                    break;
                case 'BLESSING':
                    $tag = 'BLES';
                    break;
                case 'BURIAL':
                    $tag = 'BURI';
                    break;
                case 'CALL_NUMBER':
                    $tag = 'CALN';
                    break;
                case 'CASTE':
                    $tag = 'CAST';
                    break;
                case 'CAUSE':
                    $tag = 'CAUS';
                    break;
                case 'CENSUS':
                    $tag = 'CENS';
                    break;
                case 'CHANGE':
                    $tag = 'CHAN';
                    break;
                case 'CHARACTER':
                    $tag = 'CHAR';
                    break;
                case 'CHILD':
                    $tag = 'CHIL';
                    break;
                case 'CHILDREN_COUNT':
                    $tag = 'NCHI';
                    break;
                case 'CHRISTENING':
                    $tag = 'CHR';
                    break;
                case 'CONCATENATION':
                    $tag = 'CONC';
                    break;
                case 'CONFIRMATION':
                    $tag = 'CONF';
                    break;
                case 'CONFIRMATION_LDS':
                    $tag = 'CONL';
                    break;
                case 'CONTINUED':
                    $tag = 'CONT';
                    break;
                case 'COPYRIGHT':
                    $tag = 'COPR';
                    break;
                case 'CORPORATE':
                    $tag = 'CORP';
                    break;
                case 'COUNTRY':
                    $tag = 'CTRY';
                    break;
                case 'CREMATION':
                    $tag = 'CREM';
                    break;
                case 'DATE':
                    // Preserve text from INT dates
                    if (strpos($data, '(') !== false) {
                        [$date, $text] = explode('(', $data, 2);
                        $text = ' (' . $text;
                    } else {
                        $date = $data;
                        $text = '';
                    }
                    // Capitals
                    $date = strtoupper($date);
                    // Temporarily add leading/trailing spaces, to allow efficient matching below
                    $date = " {$date} ";
                    // Ensure space digits and letters
                    $date = preg_replace('/([A-Z])(\d)/', '$1 $2', $date);
                    $date = preg_replace('/(\d)([A-Z])/', '$1 $2', $date);
                    // Ensure space before/after calendar escapes
                    $date = preg_replace('/@#[^@]+@/', ' $0 ', $date);
                    // "BET." => "BET"
                    $date = preg_replace('/(\w\w)\./', '$1', $date);
                    // "CIR" => "ABT"
                    $date = str_replace(' CIR ', ' ABT ', $date);
                    $date = str_replace(' APX ', ' ABT ', $date);
                    // B.C. => BC (temporarily, to allow easier handling of ".")
                    $date = str_replace(' B.C. ', ' BC ', $date);
                    // TMG uses "EITHER X OR Y"
                    $date = preg_replace('/^ EITHER (.+) OR (.+)/', ' BET $1 AND $2', $date);
                    // "BET X - Y " => "BET X AND Y"
                    $date = preg_replace('/^(.* BET .+) - (.+)/', '$1 AND $2', $date);
                    $date = preg_replace('/^(.* FROM .+) - (.+)/', '$1 TO $2', $date);
                    // "@#ESC@ FROM X TO Y" => "FROM @#ESC@ X TO @#ESC@ Y"
                    $date = preg_replace('/^ +(@#[^@]+@) +FROM +(.+) +TO +(.+)/', ' FROM $1 $2 TO $1 $3', $date);
                    $date = preg_replace('/^ +(@#[^@]+@) +BET +(.+) +AND +(.+)/', ' BET $1 $2 AND $1 $3', $date);
                    // "@#ESC@ AFT X" => "AFT @#ESC@ X"
                    $date = preg_replace('/^ +(@#[^@]+@) +(FROM|BET|TO|AND|BEF|AFT|CAL|EST|INT|ABT) +(.+)/', ' $2 $1 $3', $date);
                    // Ignore any remaining punctuation, e.g. "14-MAY, 1900" => "14 MAY 1900"
                    // (don't change "/" - it is used in NS/OS dates)
                    $date = preg_replace('/[.,:;-]/', ' ', $date);
                    // BC => B.C.
                    $date = str_replace(' BC ', ' B.C. ', $date);
                    // Append the "INT" text
                    $data = $date . $text;
                    break;
                case 'DEATH':
                    $tag = 'DEAT';
                    break;
                case '_DEATH_OF_SPOUSE':
                    $tag = '_DETS';
                    break;
                case '_DEGREE':
                    $tag = '_DEG';
                    break;
                case 'DESCENDANTS':
                    $tag = 'DESC';
                    break;
                case 'DESCENDANT_INT':
                    $tag = 'DESI';
                    break;
                case 'DESTINATION':
                    $tag = 'DEST';
                    break;
                case 'DIVORCE':
                    $tag = 'DIV';
                    break;
                case 'DIVORCE_FILED':
                    $tag = 'DIVF';
                    break;
                case 'EDUCATION':
                    $tag = 'EDUC';
                    break;
                case 'EMIGRATION':
                    $tag = 'EMIG';
                    break;
                case 'ENDOWMENT':
                    $tag = 'ENDL';
                    break;
                case 'ENGAGEMENT':
                    $tag = 'ENGA';
                    break;
                case 'EVENT':
                    $tag = 'EVEN';
                    break;
                case 'FACSIMILE':
                    $tag = 'FAX';
                    break;
                case 'FAMILY':
                    $tag = 'FAM';
                    break;
                case 'FAMILY_CHILD':
                    $tag = 'FAMC';
                    break;
                case 'FAMILY_FILE':
                    $tag = 'FAMF';
                    break;
                case 'FAMILY_SPOUSE':
                    $tag = 'FAMS';
                    break;
                case 'FIRST_COMMUNION':
                    $tag = 'FCOM';
                    break;
                case '_FILE':
                    $tag = 'FILE';
                    break;
                case 'FORMAT':
                case 'FORM':
                    $tag = 'FORM';
                    // Consistent commas
                    $data = preg_replace('/ *, */', ', ', $data);
                    break;
                case 'GEDCOM':
                    $tag = 'GEDC';
                    break;
                case 'GIVEN_NAME':
                    $tag = 'GIVN';
                    break;
                case 'GRADUATION':
                    $tag = 'GRAD';
                    break;
                case 'HEADER':
                case 'HEAD':
                    $tag = 'HEAD';
                    // HEAD records don't have an XREF or DATA
                    if ($level === '0') {
                        $xref = '';
                        $data = '';
                    }
                    break;
                case 'HUSBAND':
                    $tag = 'HUSB';
                    break;
                case 'IDENT_NUMBER':
                    $tag = 'IDNO';
                    break;
                case 'IMMIGRATION':
                    $tag = 'IMMI';
                    break;
                case 'INDIVIDUAL':
                    $tag = 'INDI';
                    break;
                case 'LANGUAGE':
                    $tag = 'LANG';
                    break;
                case 'LATITUDE':
                    $tag = 'LATI';
                    break;
                case 'LONGITUDE':
                    $tag = 'LONG';
                    break;
                case 'MARRIAGE':
                    $tag = 'MARR';
                    break;
                case 'MARRIAGE_BANN':
                    $tag = 'MARB';
                    break;
                case 'MARRIAGE_COUNT':
                    $tag = 'NMR';
                    break;
                case 'MARRIAGE_CONTRACT':
                    $tag = 'MARC';
                    break;
                case 'MARRIAGE_LICENSE':
                    $tag = 'MARL';
                    break;
                case 'MARRIAGE_SETTLEMENT':
                    $tag = 'MARS';
                    break;
                case 'MEDIA':
                    $tag = 'MEDI';
                    break;
                case '_MEDICAL':
                    $tag = '_MDCL';
                    break;
                case '_MILITARY_SERVICE':
                    $tag = '_MILT';
                    break;
                case 'NAME':
                    // Tidy up whitespace
                    $data = preg_replace('/  +/', ' ', trim($data));
                    break;
                case 'NAME_PREFIX':
                    $tag = 'NPFX';
                    break;
                case 'NAME_SUFFIX':
                    $tag = 'NSFX';
                    break;
                case 'NATIONALITY':
                    $tag = 'NATI';
                    break;
                case 'NATURALIZATION':
                    $tag = 'NATU';
                    break;
                case 'NICKNAME':
                    $tag = 'NICK';
                    break;
                case 'OBJECT':
                    $tag = 'OBJE';
                    break;
                case 'OCCUPATION':
                    $tag = 'OCCU';
                    break;
                case 'ORDINANCE':
                    $tag = 'ORDI';
                    break;
                case 'ORDINATION':
                    $tag = 'ORDN';
                    break;
                case 'PEDIGREE':
                case 'PEDI':
                    $tag = 'PEDI';
                    // PEDI values are lower case
                    $data = strtolower($data);
                    break;
                case 'PHONE':
                    $tag = 'PHON';
                    break;
                case 'PHONETIC':
                    $tag = 'FONE';
                    break;
                case 'PHY_DESCRIPTION':
                    $tag = 'DSCR';
                    break;
                case 'PLACE':
                case 'PLAC':
                    $tag = 'PLAC';
                    // Consistent commas
                    $data = preg_replace('/ *, */', ', ', $data);
                    // The Master Genealogist stores LAT/LONG data in the PLAC field, e.g. Pennsylvania, USA, 395945N0751013W
                    if (preg_match('/(.*), (\d\d)(\d\d)(\d\d)([NS])(\d\d\d)(\d\d)(\d\d)([EW])$/', $data, $match)) {
                        $data =
                            $match[1] . "\n" .
                            ($level + 1) . " MAP\n" .
                            ($level + 2) . ' LATI ' . ($match[5] . round($match[2] + ($match[3] / 60) + ($match[4] / 3600), 4)) . "\n" .
                            ($level + 2) . ' LONG ' . ($match[9] . round($match[6] + ($match[7] / 60) + ($match[8] / 3600), 4));
                    }
                    break;
                case 'POSTAL_CODE':
                    $tag = 'POST';
                    break;
                case 'PROBATE':
                    $tag = 'PROB';
                    break;
                case 'PROPERTY':
                    $tag = 'PROP';
                    break;
                case 'PUBLICATION':
                    $tag = 'PUBL';
                    break;
                case 'QUALITY_OF_DATA':
                    $tag = 'QUAL';
                    break;
                case 'REC_FILE_NUMBER':
                    $tag = 'RFN';
                    break;
                case 'REC_ID_NUMBER':
                    $tag = 'RIN';
                    break;
                case 'REFERENCE':
                    $tag = 'REFN';
                    break;
                case 'RELATIONSHIP':
                    $tag = 'RELA';
                    break;
                case 'RELIGION':
                    $tag = 'RELI';
                    break;
                case 'REPOSITORY':
                    $tag = 'REPO';
                    break;
                case 'RESIDENCE':
                    $tag = 'RESI';
                    break;
                case 'RESTRICTION':
                case 'RESN':
                    $tag = 'RESN';
                    // RESN values are lower case (confidential, privacy, locked, none)
                    $data = strtolower($data);
                    if ($data === 'invisible') {
                        $data = 'confidential'; // From old versions of Legacy.
                    }
                    break;
                case 'RETIREMENT':
                    $tag = 'RETI';
                    break;
                case 'ROMANIZED':
                    $tag = 'ROMN';
                    break;
                case 'SEALING_CHILD':
                    $tag = 'SLGC';
                    break;
                case 'SEALING_SPOUSE':
                    $tag = 'SLGS';
                    break;
                case 'SOC_SEC_NUMBER':
                    $tag = 'SSN';
                    break;
                case 'SEX':
                    $data = strtoupper($data);
                    break;
                case 'SOURCE':
                    $tag = 'SOUR';
                    break;
                case 'STATE':
                    $tag = 'STAE';
                    break;
                case 'STATUS':
                case 'STAT':
                    $tag = 'STAT';
                    if ($data === 'CANCELLED') {
                        // PhpGedView mis-spells this tag - correct it.
                        $data = 'CANCELED';
                    }
                    break;
                case 'SUBMISSION':
                    $tag = 'SUBN';
                    break;
                case 'SUBMITTER':
                    $tag = 'SUBM';
                    break;
                case 'SURNAME':
                    $tag = 'SURN';
                    break;
                case 'SURN_PREFIX':
                    $tag = 'SPFX';
                    break;
                case 'TEMPLE':
                case 'TEMP':
                    $tag = 'TEMP';
                    // Temple codes are upper case
                    $data = strtoupper($data);
                    break;
                case 'TITLE':
                    $tag = 'TITL';
                    break;
                case 'TRAILER':
                case 'TRLR':
                    $tag = 'TRLR';
                    // TRLR records don't have an XREF or DATA
                    if ($level === '0') {
                        $xref = '';
                        $data = '';
                    }
                    break;
                case 'VERSION':
                    $tag = 'VERS';
                    break;
                case 'WEB':
                    $tag = 'WWW';
                    break;
            }
            // Suppress "Y", for facts/events with a DATE or PLAC
            if ($data === 'y') {
                $data = 'Y';
            }
            if ($level === '1' && $data === 'Y') {
                for ($i = $n + 1; $i < $num_matches - 1 && $matches[$i][1] !== '1'; ++$i) {
                    if ($matches[$i][3] === 'DATE' || $matches[$i][3] === 'PLAC') {
                        $data = '';
                        break;
                    }
                }
            }
            // Reassemble components back into a single line
            switch ($tag) {
                default:
                    // Remove tabs and multiple/leading/trailing spaces
                    if (strpos($data, "\t") !== false) {
                        $data = str_replace("\t", ' ', $data);
                    }
                    if (substr($data, 0, 1) === ' ' || substr($data, -1, 1) === ' ') {
                        $data = trim($data);
                    }
                    while (strpos($data, '  ')) {
                        $data = str_replace('  ', ' ', $data);
                    }
                    $newrec .= ($newrec ? "\n" : '') . $level . ' ' . ($level === '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag !== 'NOTE' ? '' : ' ' . $data);
                    break;
                case 'NOTE':
                case 'TEXT':
                case 'DATA':
                case 'CONT':
                    $newrec .= ($newrec ? "\n" : '') . $level . ' ' . ($level === '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag !== 'NOTE' ? '' : ' ' . $data);
                    break;
                case 'FILE':
                    // Strip off the user-defined path prefix
                    $GEDCOM_MEDIA_PATH = $tree->getPreference('GEDCOM_MEDIA_PATH');
                    if ($GEDCOM_MEDIA_PATH && strpos($data, $GEDCOM_MEDIA_PATH) === 0) {
                        $data = substr($data, strlen($GEDCOM_MEDIA_PATH));
                    }
                    // convert backslashes in filenames to forward slashes
                    $data = preg_replace("/\\\\/", '/', $data);

                    $newrec .= ($newrec ? "\n" : '') . $level . ' ' . ($level === '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag !== 'NOTE' ? '' : ' ' . $data);
                    break;
                case 'CONC':
                    // Merge CONC lines, to simplify access later on.
                    $newrec .= ($tree->getPreference('WORD_WRAPPED_NOTES') ? ' ' : '') . $data;
                    break;
            }
        }

        return $newrec;
    }

    /**
     * import record into database
     * this function will parse the given gedcom record and add it to the database
     *
     * @param string $gedrec the raw gedcom record to parse
     * @param Tree   $tree   import the record into this tree
     * @param bool   $update whether or not this is an updated record that has been accepted
     *
     * @return void
     * @throws GedcomErrorException
     */
    public static function importRecord($gedrec, Tree $tree, $update): void
    {
        $tree_id = $tree->id();

        // Escaped @ signs (only if importing from file)
        if (!$update) {
            $gedrec = str_replace('@@', '@', $gedrec);
        }

        // Standardise gedcom format
        $gedrec = self::reformatRecord($gedrec, $tree);

        // import different types of records
        if (preg_match('/^0 @(' . Gedcom::REGEX_XREF . ')@ (' . Gedcom::REGEX_TAG . ')/', $gedrec, $match)) {
            [, $xref, $type] = $match;
            // check for a _UID, if the record doesn't have one, add one
            if ($tree->getPreference('GENERATE_UIDS') && !strpos($gedrec, "\n1 _UID ")) {
                $gedrec .= "\n1 _UID " . GedcomTag::createUid();
            }
        } elseif (preg_match('/0 (HEAD|TRLR)/', $gedrec, $match)) {
            $type = $match[1];
            $xref = $type; // For HEAD/TRLR, use type as pseudo XREF.
        } else {
            throw new GedcomErrorException($gedrec);
        }

        // If the user has downloaded their GEDCOM data (containing media objects) and edited it
        // using an application which does not support (and deletes) media objects, then add them
        // back in.
        if ($tree->getPreference('keep_media') && $xref) {
            $old_linked_media = DB::table('link')
                ->where('l_from', '=', $xref)
                ->where('l_file', '=', $tree_id)
                ->where('l_type', '=', 'OBJE')
                ->pluck('l_to');

            foreach ($old_linked_media as $media_id) {
                $gedrec .= "\n1 OBJE @" . $media_id . '@';
            }
        }

        switch ($type) {
            case 'INDI':
                // Convert inline media into media objects
                $gedrec = self::convertInlineMedia($tree, $gedrec);

                $record = new Individual($xref, $gedrec, null, $tree);
                if (preg_match('/\n1 RIN (.+)/', $gedrec, $match)) {
                    $rin = $match[1];
                } else {
                    $rin = $xref;
                }

                DB::table('individuals')->insert([
                    'i_id'     => $xref,
                    'i_file'   => $tree_id,
                    'i_rin'    => $rin,
                    'i_sex'    => $record->sex(),
                    'i_gedcom' => $gedrec,
                ]);

                // Update the cross-reference/index tables.
                self::updatePlaces($xref, $tree, $gedrec);
                self::updateDates($xref, $tree_id, $gedrec);
                self::updateLinks($xref, $tree_id, $gedrec);
                self::updateNames($xref, $tree_id, $record);
                break;
            case 'FAM':
                // Convert inline media into media objects
                $gedrec = self::convertInlineMedia($tree, $gedrec);

                if (preg_match('/\n1 HUSB @(' . Gedcom::REGEX_XREF . ')@/', $gedrec, $match)) {
                    $husb = $match[1];
                } else {
                    $husb = '';
                }
                if (preg_match('/\n1 WIFE @(' . Gedcom::REGEX_XREF . ')@/', $gedrec, $match)) {
                    $wife = $match[1];
                } else {
                    $wife = '';
                }
                $nchi = preg_match_all('/\n1 CHIL @(' . Gedcom::REGEX_XREF . ')@/', $gedrec, $match);
                if (preg_match('/\n1 NCHI (\d+)/', $gedrec, $match)) {
                    $nchi = max($nchi, $match[1]);
                }

                DB::table('families')->insert([
                    'f_id'      => $xref,
                    'f_file'    => $tree_id,
                    'f_husb'    => $husb,
                    'f_wife'    => $wife,
                    'f_gedcom'  => $gedrec,
                    'f_numchil' => $nchi,
                ]);

                // Update the cross-reference/index tables.
                self::updatePlaces($xref, $tree, $gedrec);
                self::updateDates($xref, $tree_id, $gedrec);
                self::updateLinks($xref, $tree_id, $gedrec);
                break;
            case 'SOUR':
                // Convert inline media into media objects
                $gedrec = self::convertInlineMedia($tree, $gedrec);

                $record = new Source($xref, $gedrec, null, $tree);
                if (preg_match('/\n1 TITL (.+)/', $gedrec, $match)) {
                    $name = $match[1];
                } elseif (preg_match('/\n1 ABBR (.+)/', $gedrec, $match)) {
                    $name = $match[1];
                } else {
                    $name = $xref;
                }

                DB::table('sources')->insert([
                    's_id'     => $xref,
                    's_file'   => $tree_id,
                    's_name'   => mb_substr($name, 0, 255),
                    's_gedcom' => $gedrec,
                ]);

                // Update the cross-reference/index tables.
                self::updateLinks($xref, $tree_id, $gedrec);
                self::updateNames($xref, $tree_id, $record);
                break;
            case 'REPO':
                // Convert inline media into media objects
                $gedrec = self::convertInlineMedia($tree, $gedrec);

                $record = new Repository($xref, $gedrec, null, $tree);

                DB::table('other')->insert([
                    'o_id'     => $xref,
                    'o_file'   => $tree_id,
                    'o_type'   => 'REPO',
                    'o_gedcom' => $gedrec,
                ]);

                // Update the cross-reference/index tables.
                self::updateLinks($xref, $tree_id, $gedrec);
                self::updateNames($xref, $tree_id, $record);
                break;
            case 'NOTE':
                $record = new Note($xref, $gedrec, null, $tree);

                DB::table('other')->insert([
                    'o_id'     => $xref,
                    'o_file'   => $tree_id,
                    'o_type'   => 'NOTE',
                    'o_gedcom' => $gedrec,
                ]);

                // Update the cross-reference/index tables.
                self::updateLinks($xref, $tree_id, $gedrec);
                self::updateNames($xref, $tree_id, $record);
                break;
            case 'OBJE':
                $record = new Media($xref, $gedrec, null, $tree);

                DB::table('media')->insert([
                    'm_id'     => $xref,
                    'm_file'   => $tree_id,
                    'm_gedcom' => $gedrec,
                ]);

                foreach ($record->mediaFiles() as $media_file) {
                    DB::table('media_file')->insert([
                        'm_id'                 => $xref,
                        'm_file'               => $tree_id,
                        'multimedia_file_refn' => mb_substr($media_file->filename(), 0, 248),
                        'multimedia_format'    => mb_substr($media_file->format(), 0, 4),
                        'source_media_type'    => mb_substr($media_file->type(), 0, 15),
                        'descriptive_title'    => mb_substr($media_file->title(), 0, 248),
                    ]);
                }

                // Update the cross-reference/index tables.
                self::updateLinks($xref, $tree_id, $gedrec);
                self::updateNames($xref, $tree_id, $record);
                break;
            default: // HEAD, TRLR, SUBM, SUBN, and custom record types.
                // Force HEAD records to have a creation date.
                if ($type === 'HEAD' && strpos($gedrec, "\n1 DATE ") === false) {
                    $gedrec .= "\n1 DATE " . date('j M Y');
                }

                DB::table('other')->insert([
                    'o_id'     => $xref,
                    'o_file'   => $tree_id,
                    'o_type'   => mb_substr($type, 0, 15),
                    'o_gedcom' => $gedrec,
                ]);

                // Update the cross-reference/index tables.
                self::updateLinks($xref, $tree_id, $gedrec);
                break;
        }
    }

    /**
     * Extract all level 2 places from the given record and insert them into the places table
     *
     * @param string $xref
     * @param Tree   $tree
     * @param string $gedrec
     *
     * @return void
     */
    public static function updatePlaces(string $xref, Tree $tree, string $gedrec): void
    {
        preg_match_all('/^[2-9] PLAC (.+)/m', $gedrec, $matches);

        $places = array_unique($matches[1]);

        foreach ($places as $place_name) {
            $place = new Place($place_name, $tree);

            // Calling Place::id() will create the entry in the database, if it doesn't already exist.
            // Link the place to the record
            while ($place->id() !== 0) {
                try {
                    DB::table('placelinks')->insert([
                        'pl_p_id' => $place->id(),
                        'pl_gid'  => $xref,
                        'pl_file' => $tree->id(),
                    ]);
                } catch (PDOException $ex) {
                    // Already linked this place - so presumably also any parent places.
                    break;
                }

                $place = $place->parent();
            }
        }
    }

    /**
     * Extract all the dates from the given record and insert them into the database.
     *
     * @param string $xref
     * @param int    $ged_id
     * @param string $gedrec
     *
     * @return void
     */
    public static function updateDates($xref, $ged_id, $gedrec): void
    {
        if (strpos($gedrec, '2 DATE ') && preg_match_all("/\n1 (\w+).*(?:\n[2-9].*)*(?:\n2 DATE (.+))(?:\n[2-9].*)*/", $gedrec, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fact = $match[1];
                if (($fact === 'FACT' || $fact === 'EVEN') && preg_match("/\n2 TYPE ([A-Z]{3,5})/", $match[0], $tmatch)) {
                    $fact = $tmatch[1];
                }
                $date = new Date($match[2]);
                DB::table('dates')->insert([
                    'd_day'        => $date->minimumDate()->day,
                    'd_month'      => $date->minimumDate()->format('%O'),
                    'd_mon'        => $date->minimumDate()->month,
                    'd_year'       => $date->minimumDate()->year,
                    'd_julianday1' => $date->minimumDate()->minimumJulianDay(),
                    'd_julianday2' => $date->minimumDate()->maximumJulianDay(),
                    'd_fact'       => $fact,
                    'd_gid'        => $xref,
                    'd_file'       => $ged_id,
                    'd_type'       => $date->minimumDate()->format('%@'),
                ]);

                if ($date->minimumDate() !== $date->maximumDate()) {
                    DB::table('dates')->insert([
                        'd_day'        => $date->maximumDate()->day,
                        'd_month'      => $date->maximumDate()->format('%O'),
                        'd_mon'        => $date->maximumDate()->month,
                        'd_year'       => $date->maximumDate()->year,
                        'd_julianday1' => $date->maximumDate()->minimumJulianDay(),
                        'd_julianday2' => $date->maximumDate()->maximumJulianDay(),
                        'd_fact'       => $fact,
                        'd_gid'        => $xref,
                        'd_file'       => $ged_id,
                        'd_type'       => $date->minimumDate()->format('%@'),
                    ]);
                }
            }
        }
    }

    /**
     * Extract all the links from the given record and insert them into the database
     *
     * @param string $xref
     * @param int    $ged_id
     * @param string $gedrec
     *
     * @return void
     */
    public static function updateLinks($xref, $ged_id, $gedrec): void
    {
        if (preg_match_all('/^\d+ (' . Gedcom::REGEX_TAG . ') @(' . Gedcom::REGEX_XREF . ')@/m', $gedrec, $matches, PREG_SET_ORDER)) {
            $data = [];
            foreach ($matches as $match) {
                // Include each link once only.
                if (!in_array($match[1] . $match[2], $data, true)) {
                    $data[] = $match[1] . $match[2];
                    try {
                        DB::table('link')->insert([
                            'l_from' => $xref,
                            'l_to'   => $match[2],
                            'l_type' => $match[1],
                            'l_file' => $ged_id,
                        ]);
                    } catch (PDOException $ex) {
                        // Ignore any errors, which may be caused by "duplicates" that differ on case/collation, e.g. "S1" and "s1"
                    }
                }
            }
        }
    }

    /**
     * Extract all the names from the given record and insert them into the database.
     *
     * @param string       $xref
     * @param int          $ged_id
     * @param GedcomRecord $record
     *
     * @return void
     */
    public static function updateNames($xref, $ged_id, GedcomRecord $record): void
    {
        foreach ($record->getAllNames() as $n => $name) {
            if ($record instanceof Individual) {
                if ($name['givn'] === '@P.N.') {
                    $soundex_givn_std = null;
                    $soundex_givn_dm  = null;
                } else {
                    $soundex_givn_std = Soundex::russell($name['givn']);
                    $soundex_givn_dm  = Soundex::daitchMokotoff($name['givn']);
                }
                if ($name['surn'] === '@N.N.') {
                    $soundex_surn_std = null;
                    $soundex_surn_dm  = null;
                } else {
                    $soundex_surn_std = Soundex::russell($name['surname']);
                    $soundex_surn_dm  = Soundex::daitchMokotoff($name['surname']);
                }
                DB::table('name')->insert([
                    'n_file'             => $ged_id,
                    'n_id'               => $xref,
                    'n_num'              => $n,
                    'n_type'             => $name['type'],
                    'n_sort'             => mb_substr($name['sort'], 0, 255),
                    'n_full'             => mb_substr($name['fullNN'], 0, 255),
                    'n_surname'          => mb_substr($name['surname'], 0, 255),
                    'n_surn'             => mb_substr($name['surn'], 0, 255),
                    'n_givn'             => mb_substr($name['givn'], 0, 255),
                    'n_soundex_givn_std' => $soundex_givn_std,
                    'n_soundex_surn_std' => $soundex_surn_std,
                    'n_soundex_givn_dm'  => $soundex_givn_dm,
                    'n_soundex_surn_dm'  => $soundex_surn_dm,
                ]);
            } else {
                DB::table('name')->insert([
                    'n_file' => $ged_id,
                    'n_id'   => $xref,
                    'n_num'  => $n,
                    'n_type' => $name['type'],
                    'n_sort' => mb_substr($name['sort'], 0, 255),
                    'n_full' => mb_substr($name['fullNN'], 0, 255),
                ]);
            }
        }
    }

    /**
     * Extract inline media data, and convert to media objects.
     *
     * @param Tree   $tree
     * @param string $gedrec
     *
     * @return string
     */
    public static function convertInlineMedia(Tree $tree, $gedrec): string
    {
        while (preg_match('/\n1 OBJE(?:\n[2-9].+)+/', $gedrec, $match)) {
            $gedrec = str_replace($match[0], self::createMediaObject(1, $match[0], $tree), $gedrec);
        }
        while (preg_match('/\n2 OBJE(?:\n[3-9].+)+/', $gedrec, $match)) {
            $gedrec = str_replace($match[0], self::createMediaObject(2, $match[0], $tree), $gedrec);
        }
        while (preg_match('/\n3 OBJE(?:\n[4-9].+)+/', $gedrec, $match)) {
            $gedrec = str_replace($match[0], self::createMediaObject(3, $match[0], $tree), $gedrec);
        }

        return $gedrec;
    }

    /**
     * Create a new media object, from inline media data.
     *
     * @param int    $level
     * @param string $gedrec
     * @param Tree   $tree
     *
     * @return string
     */
    public static function createMediaObject($level, $gedrec, Tree $tree): string
    {
        if (preg_match('/\n\d FILE (.+)/', $gedrec, $file_match)) {
            $file = $file_match[1];
        } else {
            $file = '';
        }

        if (preg_match('/\n\d TITL (.+)/', $gedrec, $file_match)) {
            $titl = $file_match[1];
        } else {
            $titl = '';
        }

        // Have we already created a media object with the same title/filename?
        $xref = DB::table('media_file')
            ->where('m_file', '=', $tree->id())
            ->where('descriptive_title', '=', $titl)
            ->where('multimedia_file_refn', '=', mb_substr($file, 0, 248))
            ->value('m_id');

        if ($xref === null) {
            $xref = $tree->getNewXref();
            // renumber the lines
            $gedrec = preg_replace_callback('/\n(\d+)/', static function (array $m) use ($level): string {
                return "\n" . ($m[1] - $level);
            }, $gedrec);
            // convert to an object
            $gedrec = str_replace("\n0 OBJE\n", '0 @' . $xref . "@ OBJE\n", $gedrec);

            // Fix Legacy GEDCOMS
            $gedrec = preg_replace('/\n1 FORM (.+)\n1 FILE (.+)\n1 TITL (.+)/', "\n1 FILE $2\n2 FORM $1\n2 TITL $3", $gedrec);

            // Fix FTB GEDCOMS
            $gedrec = preg_replace('/\n1 FORM (.+)\n1 TITL (.+)\n1 FILE (.+)/', "\n1 FILE $3\n2 FORM $1\n2 TITL $2", $gedrec);

            // Fix RM7 GEDCOMS
            $gedrec = preg_replace('/\n1 FILE (.+)\n1 FORM (.+)\n1 TITL (.+)/', "\n1 FILE $1\n2 FORM $2\n2 TITL $3", $gedrec);

            // Create new record
            $record = new Media($xref, $gedrec, null, $tree);

            DB::table('media')->insert([
                'm_id'     => $xref,
                'm_file'   => $tree->id(),
                'm_gedcom' => $gedrec,
            ]);

            foreach ($record->mediaFiles() as $media_file) {
                DB::table('media_file')->insert([
                    'm_id'                 => $xref,
                    'm_file'               => $tree->id(),
                    'multimedia_file_refn' => mb_substr($media_file->filename(), 0, 248),
                    'multimedia_format'    => mb_substr($media_file->format(), 0, 4),
                    'source_media_type'    => mb_substr($media_file->type(), 0, 15),
                    'descriptive_title'    => mb_substr($media_file->title(), 0, 248),
                ]);
            }
        }

        return "\n" . $level . ' OBJE @' . $xref . '@';
    }

    /**
     * update a record in the database
     *
     * @param string $gedrec
     * @param Tree   $tree
     * @param bool   $delete
     *
     * @return void
     * @throws GedcomErrorException
     */
    public static function updateRecord($gedrec, Tree $tree, bool $delete): void
    {
        if (preg_match('/^0 @(' . Gedcom::REGEX_XREF . ')@ (' . Gedcom::REGEX_TAG . ')/', $gedrec, $match)) {
            [, $gid, $type] = $match;
        } elseif (preg_match('/^0 (HEAD)(?:\n|$)/', $gedrec, $match)) {
            // The HEAD record has no XREF.  Any others?
            $gid  = $match[1];
            $type = $match[1];
        } else {
            throw new GedcomErrorException($gedrec);
        }

        // Place links
        DB::table('placelinks')
            ->where('pl_gid', '=', $gid)
            ->where('pl_file', '=', $tree->id())
            ->delete();

        // Orphaned places.  If we're deleting  "Westminster, London, England",
        // then we may also need to delete "London, England" and "England".
        do {
            $affected = DB::table('places')
                ->leftJoin('placelinks', static function (JoinClause $join): void {
                    $join
                        ->on('p_id', '=', 'pl_p_id')
                        ->on('p_file', '=', 'pl_file');
                })
                ->whereNull('pl_p_id')
                ->delete();
        } while ($affected > 0);

        DB::table('dates')
            ->where('d_gid', '=', $gid)
            ->where('d_file', '=', $tree->id())
            ->delete();

        DB::table('name')
            ->where('n_id', '=', $gid)
            ->where('n_file', '=', $tree->id())
            ->delete();

        DB::table('link')
            ->where('l_from', '=', $gid)
            ->where('l_file', '=', $tree->id())
            ->delete();

        switch ($type) {
            case 'INDI':
                DB::table('individuals')
                    ->where('i_id', '=', $gid)
                    ->where('i_file', '=', $tree->id())
                    ->delete();
                break;

            case 'FAM':
                DB::table('families')
                    ->where('f_id', '=', $gid)
                    ->where('f_file', '=', $tree->id())
                    ->delete();
                break;

            case 'SOUR':
                DB::table('sources')
                    ->where('s_id', '=', $gid)
                    ->where('s_file', '=', $tree->id())
                    ->delete();
                break;

            case 'OBJE':
                DB::table('media_file')
                    ->where('m_id', '=', $gid)
                    ->where('m_file', '=', $tree->id())
                    ->delete();

                DB::table('media')
                    ->where('m_id', '=', $gid)
                    ->where('m_file', '=', $tree->id())
                    ->delete();
                break;

            default:
                DB::table('other')
                    ->where('o_id', '=', $gid)
                    ->where('o_file', '=', $tree->id())
                    ->delete();
                break;
        }

        if (!$delete) {
            self::importRecord($gedrec, $tree, true);
        }
    }
}
