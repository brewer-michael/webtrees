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

use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Tree;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function response;

/**
 * Autocomplete for Select2 based controls.
 */
abstract class AbstractSelect2Handler implements RequestHandlerInterface
{
    // For clients that request one page of data at a time.
    private const RESULTS_PER_PAGE = 20;

    // Minimum number of characters for a search.
    public const MINIMUM_INPUT_LENGTH = 2;

    // Wait for the user to pause typing before sending request.
    public const AJAX_DELAY = 350;

    /** @var SearchService */
    protected $search_service;

    /**
     * AutocompleteController constructor.
     *
     * @param SearchService $search_service
     */
    public function __construct(SearchService $search_service)
    {
        $this->search_service = $search_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $query = $request->getParsedBody()['q'] ?? '';
        assert(strlen($query) >= self::MINIMUM_INPUT_LENGTH);

        $page = (int) ($request->getParsedBody()['page'] ?? 1);

        // Fetch one more row than we need, so we can know if more rows exist.
        $offset = ($page - 1) * self::RESULTS_PER_PAGE;
        $limit  = self::RESULTS_PER_PAGE + 1;

        // Perform the search.
        $results = $this->search($tree, $query, $offset, $limit);

        return response([
            'results'    => $results->slice(0, self::RESULTS_PER_PAGE)->all(),
            'pagination' => [
                'more' => $results->count() > self::RESULTS_PER_PAGE,
            ],
        ]);
    }

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
    abstract protected function search(Tree $tree, string $query, int $offset, int $limit): Collection;
}
