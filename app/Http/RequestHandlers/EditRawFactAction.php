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

namespace Fisharebest\Webtrees\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function is_string;
use function preg_replace;
use function redirect;
use function trim;

/**
 * Edit the raw GEDCOM of a fact.
 */
class EditRawFactAction implements RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $xref = $request->getAttribute('xref');
        assert(is_string($xref));

        $record = GedcomRecord::getInstance($xref, $tree);

        $fact_id = $request->getAttribute('fact_id');
        $gedcom  = $request->getParsedBody()['gedcom'];

        // Cleanup the client’s bad editing?
        $gedcom = preg_replace('/[\r\n]+/', "\n", $gedcom); // Empty lines
        $gedcom = trim($gedcom); // Leading/trailing spaces
        $record = Auth::checkRecordAccess($record, true);

        foreach ($record->facts() as $fact) {
            if (!$fact->isPendingDeletion() && $fact->id() === $fact_id && $fact->canEdit()) {
                $record->updateFact($fact_id, $gedcom, false);
                break;
            }
        }

        return redirect($record->url());
    }
}
