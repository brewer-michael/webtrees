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

namespace Fisharebest\Webtrees\Services;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\GedcomTag;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_map;
use function assert;
use function intdiv;
use function pathinfo;
use function preg_match;
use function sha1;
use function sort;
use function str_replace;
use function strpos;
use function strtolower;
use function trim;

use const PATHINFO_EXTENSION;
use const UPLOAD_ERR_OK;

/**
 * Managing media files.
 */
class MediaFileService
{
    public const EDIT_RESTRICTIONS = [
        'locked',
    ];

    public const PRIVACY_RESTRICTIONS = [
        'none',
        'privacy',
        'confidential',
    ];

    /**
     * What is the largest file a user may upload?
     */
    public function maxUploadFilesize(): string
    {
        $bytes = UploadedFile::getMaxFilesize();
        $kb    = intdiv($bytes + 1023, 1024);

        return I18N::translate('%s KB', I18N::number($kb));
    }

    /**
     * A list of key/value options for media types.
     *
     * @param string $current
     *
     * @return array
     */
    public function mediaTypes($current = ''): array
    {
        $media_types = GedcomTag::getFileFormTypes();

        $media_types = ['' => ''] + [$current => $current] + $media_types;

        return $media_types;
    }

    /**
     * A list of media files not already linked to a media object.
     *
     * @param Tree $tree
     *
     * @return array
     */
    public function unusedFiles(Tree $tree): array
    {
        $used_files = DB::table('media_file')
            ->where('m_file', '=', $tree->id())
            ->where('multimedia_file_refn', 'NOT LIKE', 'http://%')
            ->where('multimedia_file_refn', 'NOT LIKE', 'https://%')
            ->pluck('multimedia_file_refn')
            ->all();

        $disk_files = $tree->mediaFilesystem()->listContents('', true);

        $disk_files = array_filter($disk_files, static function (array $item) {
            // Older versions of webtrees used a couple of special folders.
            return
                $item['type'] === 'file' &&
                strpos($item['path'], '/thumbs/') === false &&
                strpos($item['path'], '/watermarks/') === false;
        });

        $disk_files = array_map(static function (array $item): string {
            return $item['path'];
        }, $disk_files);

        $unused_files = array_diff($disk_files, $used_files);

        sort($unused_files);

        return array_combine($unused_files, $unused_files);
    }

    /**
     * Store an uploaded file (or URL), either to be added to a media object
     * or to create a media object.
     *
     * @param ServerRequestInterface $request
     *
     * @return string The value to be stored in the 'FILE' field of the media object.
     */
    public function uploadFile(ServerRequestInterface $request): string
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $params        = $request->getParsedBody();
        $file_location = $params['file_location'];

        switch ($file_location) {
            case 'url':
                $remote = $params['remote'];

                if (strpos($remote, '://') !== false) {
                    return $remote;
                }

                return '';

            case 'unused':
                $unused = $params['unused'];

                if ($tree->mediaFilesystem()->has($unused)) {
                    return $unused;
                }

                return '';

            case 'upload':
            default:
                $folder   = $params['folder'];
                $auto     = $params['auto'];
                $new_file = $params['new_file'];

                /** @var UploadedFileInterface|null $uploaded_file */
                $uploaded_file = $request->getUploadedFiles()['file'];
                if ($uploaded_file === null || $uploaded_file->getError() !== UPLOAD_ERR_OK) {
                    return '';
                }

                // The filename
                $new_file = str_replace('\\', '/', $new_file);
                if ($new_file !== '' && strpos($new_file, '/') === false) {
                    $file = $new_file;
                } else {
                    $file = $uploaded_file->getClientFilename();
                }

                // The folder
                $folder = str_replace('\\', '/', $folder);
                $folder = trim($folder, '/');
                if ($folder !== '') {
                    $folder .= '/';
                }

                // Generate a unique name for the file?
                if ($auto === '1' || $tree->mediaFilesystem()->has($folder . $file)) {
                    $folder    = '';
                    $extension = pathinfo($uploaded_file->getClientFilename(), PATHINFO_EXTENSION);
                    $file      = sha1((string) $uploaded_file->getStream()) . '.' . $extension;
                }

                try {
                    $tree->mediaFilesystem()->writeStream($folder . $file, $uploaded_file->getStream()->detach());

                    return $folder . $file;
                } catch (RuntimeException | InvalidArgumentException $ex) {
                    FlashMessages::addMessage(I18N::translate('There was an error uploading your file.'));

                    return '';
                }
        }
    }

    /**
     * Convert the media file attributes into GEDCOM format.
     *
     * @param string $file
     * @param string $type
     * @param string $title
     *
     * @return string
     */
    public function createMediaFileGedcom(string $file, string $type, string $title): string
    {
        if (preg_match('/\.([a-z0-9]+)/i', $file, $match)) {
            $extension = strtolower($match[1]);
            $extension = str_replace('jpg', 'jpeg', $extension);
            $extension = ' ' . $extension;
        } else {
            $extension = '';
        }

        $gedcom = '1 FILE ' . $file;
        if ($type !== '') {
            $gedcom .= "\n2 FORM" . $extension . "\n3 TYPE " . $type;
        }
        if ($title !== '') {
            $gedcom .= "\n2 TITL " . $title;
        }

        return $gedcom;
    }
}
