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

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Html;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function assert;
use function fclose;
use function fopen;
use function pathinfo;
use function redirect;
use function rewind;
use function route;
use function strtolower;

use const PATHINFO_EXTENSION;

/**
 * Save a GEDCOM file on the server.
 */
class ExportGedcomServer implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var FilesystemInterface */
    private $filesystem;

    /**
     * ExportGedcomServer constructor.
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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

        $filename = $tree->name();

        // Force a ".ged" suffix
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'ged') {
            $filename .= '.ged';
        }

        try {
            $stream = fopen('php://temp', 'wb+');
            $tree->exportGedcom($stream);
            rewind($stream);
            $this->filesystem->putStream($filename, $stream);
            fclose($stream);

            /* I18N: %s is a filename */
            FlashMessages::addMessage(I18N::translate('The family tree has been exported to %s.', Html::filename($filename)), 'success');
        } catch (Throwable $ex) {
            FlashMessages::addMessage(
                I18N::translate('The file %s could not be created.', Html::filename($filename)) . '<hr><samp dir="ltr">' . $ex->getMessage() . '</samp>',
                'danger'
            );
        }

        $url = route('manage-trees', ['tree' => $tree->name()]);

        return redirect($url);
    }
}
