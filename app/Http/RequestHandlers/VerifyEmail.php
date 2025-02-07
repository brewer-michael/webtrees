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

use Fisharebest\Localization\Locale\LocaleInterface;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\NoReplyUser;
use Fisharebest\Webtrees\Services\EmailService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\SiteUser;
use Fisharebest\Webtrees\User;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

/**
 * Acknowledge an email verification code.
 */
class VerifyEmail implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var EmailService */
    private $email_service;

    /** @var UserService */
    private $user_service;

    /**
     * MessageController constructor.
     *
     * @param EmailService $email_service
     * @param UserService  $user_service
     */
    public function __construct(EmailService $email_service, UserService $user_service)
    {
        $this->email_service = $email_service;
        $this->user_service  = $user_service;
    }

    /**
     * Respond to a verification link that was emailed to a user.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $locale = $request->getAttribute('locale');
        assert($locale instanceof LocaleInterface);

        $username = $request->getQueryParams()['username'] ?? '';
        $token    = $request->getQueryParams()['token'] ?? '';

        $title = I18N::translate('User verification');

        $user = $this->user_service->findByUserName($username);

        if ($user instanceof User && $user->getPreference('reg_hashcode') === $token) {
            foreach ($this->user_service->administrators() as $administrator) {
                // switch language to administrator settings
                I18N::init($administrator->getPreference('language'));

                $base_url = $request->getAttribute('base_url');

                /* I18N: %s is a server name/URL */
                $subject = I18N::translate('New user at %s', $base_url);

                $this->email_service->send(
                    new SiteUser(),
                    $administrator,
                    new NoReplyUser(),
                    $subject,
                    view('emails/verify-notify-text', ['user' => $user]),
                    view('emails/verify-notify-html', ['user' => $user])
                );

                $mail1_method = $administrator->getPreference('CONTACT_METHOD');

                if ($mail1_method !== 'messaging3' && $mail1_method !== 'mailto' && $mail1_method !== 'none') {
                    DB::table('message')->insert([
                        'sender'     => $username,
                        'ip_address' => $request->getAttribute('client-ip'),
                        'user_id'    => $administrator->id(),
                        'subject'    => $subject,
                        'body'       => view('emails/verify-notify-text', ['user' => $user]),
                    ]);
                }
                I18N::init($locale->languageTag());
            }

            $user
                ->setPreference('verified', '1')
                ->setPreference('reg_timestamp', date('U'))
                ->setPreference('reg_hashcode', '');

            Log::addAuthenticationLog('User ' . $username . ' verified their email address');

            return $this->viewResponse('verify-success-page', [
                'title' => $title,
            ]);
        }

        return $this->viewResponse('verify-failure-page', [
            'title' => $title,
        ]);
    }
}
