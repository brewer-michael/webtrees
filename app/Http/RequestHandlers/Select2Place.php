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

use Fisharebest\Webtrees\Place;
use Fisharebest\Webtrees\Tree;
use Illuminate\Support\Collection;

/**
 * Autocomplete for places.
 */
class Select2Place extends AbstractSelect2Handler
{
    /**
     * Perform the search
     *
     * @param Tree   $tree
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection
     */
    protected function search(Tree $tree, string $query, int $offset, int $limit): Collection
    {
        return $this->search_service
            ->searchPlaces($tree, $query, $offset, $limit)
            ->map(static function (Place $place): array {
                return [
                    'id'    => $place->gedcomName(),
                    'text'  => $place->gedcomName(),
                    'title' => ' ',
                ];
            });
    }
}
