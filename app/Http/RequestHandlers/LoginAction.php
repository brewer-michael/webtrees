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

use Exception;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Carbon;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Controllers\AbstractBaseController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function route;

/**
 * Perform a login.
 */
class LoginAction extends AbstractBaseController
{
    /** @var UpgradeService */
    private $upgrade_service;

    /** @var UserService */
    private $user_service;

    /**
     * LoginController constructor.
     *
     * @param UpgradeService $upgrade_service
     * @param UserService    $user_service
     */
    public function __construct(UpgradeService $upgrade_service, UserService $user_service)
    {
        $this->upgrade_service = $upgrade_service;
        $this->user_service    = $user_service;
    }

    /**
     * Perform a login.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree     = $request->getAttribute('tree');
        $username = $request->getParsedBody()['username'];
        $password = $request->getParsedBody()['password'];
        $url      = $request->getParsedBody()['url'];

        try {
            $this->doLogin($username, $password);

            if (Auth::isAdmin() && $this->upgrade_service->isUpgradeAvailable()) {
                FlashMessages::addMessage(I18N::translate('A new version of webtrees is available.') . ' <a class="alert-link" href="' . e(route('upgrade')) . '">' . I18N::translate('Upgrade to webtrees %s.', '<span dir="ltr">' . $this->upgrade_service->latestVersion() . '</span>') . '</a>');
            }

            // Redirect to the target URL
            $url = $url ?: route(HomePage::class);

            return redirect($url);
        } catch (Exception $ex) {
            // Failed to log in.
            FlashMessages::addMessage($ex->getMessage(), 'danger');

            return redirect(route(LoginPage::class, [
                'tree'     => $tree instanceof Tree ? $tree->name() : null,
                'username' => $username,
                'url'      => $url,
            ]));
        }
    }

    /**
     * Log in, if we can.  Throw an exception, if we can't.
     *
     * @param string $username
     * @param string $password
     *
     * @return void
     * @throws Exception
     */
    private function doLogin(string $username, string $password): void
    {
        if ($_COOKIE === []) {
            Log::addAuthenticationLog('Login failed (no session cookies): ' . $username);
            throw new Exception(I18N::translate('You cannot sign in because your browser does not accept cookies.'));
        }

        $user = $this->user_service->findByIdentifier($username);

        if ($user === null) {
            Log::addAuthenticationLog('Login failed (no such user/email): ' . $username);
            throw new Exception(I18N::translate('The username or password is incorrect.'));
        }

        if (!$user->checkPassword($password)) {
            Log::addAuthenticationLog('Login failed (incorrect password): ' . $username);
            throw new Exception(I18N::translate('The username or password is incorrect.'));
        }

        if (!$user->getPreference('verified')) {
            Log::addAuthenticationLog('Login failed (not verified by user): ' . $username);
            throw new Exception(I18N::translate('This account has not been verified. Please check your email for a verification message.'));
        }

        if (!$user->getPreference('verified_by_admin')) {
            Log::addAuthenticationLog('Login failed (not approved by admin): ' . $username);
            throw new Exception(I18N::translate('This account has not been approved. Please wait for an administrator to approve it.'));
        }

        Auth::login($user);
        Log::addAuthenticationLog('Login: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
        Auth::user()->setPreference('sessiontime', (string) Carbon::now()->unix());

        Session::put('language', Auth::user()->getPreference('language'));
        Session::put('theme', Auth::user()->getPreference('theme'));
        I18N::init(Auth::user()->getPreference('language'));
    }
}
