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

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Http\Controllers\AbstractBaseController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;

/**
 * Show a login form.
 */
class LoginPage extends AbstractBaseController
{
    /** @var TreeService */
    private $tree_service;

    /**
     * LoginPage constructor.
     *
     * @param TreeService $tree_service
     */
    public function __construct(TreeService $tree_service)
    {
        $this->tree_service = $tree_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $locale = $request->getAttribute('locale');
        assert($locale instanceof LocaleInterface);

        $tree = $request->getAttribute('tree');
        $user = $request->getAttribute('user');

        // Already logged in?
        if ($user instanceof User) {
            return redirect(route('user-page', ['tree' => $tree instanceof Tree ? $tree->name() : '']));
        }

        $url      = $request->getQueryParams()['url'] ?? '';
        $username = $request->getQueryParams()['username'] ?? '';

        // No tree?  perhaps we came here from a page without one.
        if ($tree === null) {
            $default = Site::getPreference('DEFAULT_GEDCOM');
            $tree = $this->tree_service->all()->get($default) ?? $this->tree_service->all()->first();

            if ($tree instanceof Tree) {
                return redirect(route(self::class, ['tree' => $tree->name(), 'url' => $url]));
            }
        }

        $title = I18N::translate('Sign in');

        switch (Site::getPreference('WELCOME_TEXT_AUTH_MODE')) {
            case '1':
            default:
                $welcome = I18N::translate('Anyone with a user account can access this website.');
                break;
            case '2':
                $welcome = I18N::translate('You need to be an authorized user to access this website.');
                break;
            case '3':
                $welcome = I18N::translate('You need to be a family member to access this website.');
                break;
            case '4':
                $welcome = Site::getPreference('WELCOME_TEXT_AUTH_MODE_' . $locale->languageTag());
                break;
        }

        if (Site::getPreference('USE_REGISTRATION_MODULE') === '1') {
            $welcome .= ' ' . I18N::translate('You can apply for an account using the link below.');
        }

        $can_register = Site::getPreference('USE_REGISTRATION_MODULE') === '1';

        return $this->viewResponse('login-page', [
            'can_register' => $can_register,
            'title'        => $title,
            'url'          => $url,
            'tree'         => $tree,
            'username'     => $username,
            'welcome'      => $welcome,
        ]);
    }
}
