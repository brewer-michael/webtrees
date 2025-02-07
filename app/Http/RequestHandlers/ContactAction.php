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
use Fisharebest\Webtrees\GuestUser;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\MessageService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function assert;
use function checkdnsrr;
use function e;
use function in_array;
use function preg_match;
use function preg_quote;
use function redirect;
use function route;

/**
 * Send a message from a visitor.
 */
class ContactAction implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var MessageService */
    private $message_service;

    /** @var UserService */
    private $user_service;

    /**
     * MessagePage constructor.
     *
     * @param MessageService $message_service
     * @param UserService    $user_service
     */
    public function __construct(MessageService $message_service, UserService $user_service)
    {
        $this->user_service = $user_service;
        $this->message_service = $message_service;
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

        $params     = $request->getParsedBody();
        $body       = $params['body'];
        $from_email = $params['from_email'];
        $from_name  = $params['from_name'];
        $subject    = $params['subject'];
        $to         = $params['to'];
        $url        = $params['url'];
        $ip         = $request->getAttribute('client-ip');
        $to_user    = $this->user_service->findByUserName($to);

        if ($to_user === null) {
            throw new NotFoundHttpException();
        }

        if (!in_array($to_user, $this->message_service->validContacts($tree), false)) {
            throw new AccessDeniedHttpException('Invalid contact user id');
        }

        $errors = $body === '' || $subject === '' || $from_email === '' || $from_name === '';

        if (!preg_match('/^[^@]+@([^@]+)$/', $from_email, $match) || !checkdnsrr($match[1])) {
            FlashMessages::addMessage(I18N::translate('Please enter a valid email address.'), 'danger');
            $errors = true;
        }

        $base_url = $request->getAttribute('base_url');

        if (preg_match('/(?!' . preg_quote($base_url, '/') . ')(((?:ftp|http|https):\/\/)[a-zA-Z0-9.-]+)/', $subject . $body, $match)) {
            FlashMessages::addMessage(I18N::translate('You are not allowed to send messages that contain external links.') . ' ' . /* I18N: e.g. ‘You should delete the “http://” from “http://www.example.com” and try again.’ */
                I18N::translate('You should delete the “%1$s” from “%2$s” and try again.', $match[2], $match[1]), 'danger');
            $errors = true;
        }

        if ($errors) {
            return redirect(route(ContactPage::class, [
                'body'       => $body,
                'from_email' => $from_email,
                'from_name'  => $from_name,
                'subject'    => $subject,
                'to'         => $to,
                'tree'       => $tree->name(),
                'url'        => $url,
            ]));
        }

        $sender = new GuestUser($from_email, $from_name);

        if ($this->message_service->deliverMessage($sender, $to_user, $subject, $body, $url, $ip)) {
            FlashMessages::addMessage(I18N::translate('The message was successfully sent to %s.', e($to_user->realName())), 'success');

            $url = $url ?: route('tree-page', ['tree' => $tree->name()]);

            return redirect($url);
        }

        FlashMessages::addMessage(I18N::translate('The message was not sent.'), 'danger');

        $redirect_url = route(ContactPage::class, [
            'body'       => $body,
            'from_email' => $from_email,
            'from_name'  => $from_name,
            'subject'    => $subject,
            'to'         => $to,
            'tree'       => $tree->name(),
            'url'        => $url,
        ]);

        return redirect($redirect_url);
    }
}
