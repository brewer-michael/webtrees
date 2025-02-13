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

namespace Fisharebest\Webtrees\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\GuestUser;
use Fisharebest\Webtrees\TestCase;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use function response;

/**
 * Test the AuthManager middleware.
 *
 * @covers \Fisharebest\Webtrees\Http\Middleware\AuthManager
 */
class AuthManagerTest extends TestCase
{
    /**
     * @return void
     */
    public function testAllowed(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(response('lorem ipsum'));

        $user = $this->createMock(User::class);
        $user->method('getPreference')->with('canadmin')->willReturn('0');

        $tree = $this->createMock(Tree::class);
        $tree->method('getUserPreference')->with($user, 'canedit')->willReturn('admin');

        $request    = self::createRequest()->withAttribute('tree', $tree)->withAttribute('user', $user);
        $middleware = new AuthManager();
        $response   = $middleware->process($request, $handler);

        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertSame('lorem ipsum', (string) $response->getBody());
    }

    /**
     * @return void
     */
    public function testNotAllowed(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You do not have permission to view this page.');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(response('lorem ipsum'));

        $user = $this->createMock(User::class);
        $user->method('getPreference')->with('canadmin')->willReturn('0');

        $tree = $this->createMock(Tree::class);
        $tree->method('getUserPreference')->with($user, 'canedit')->willReturn('accept');

        $request    = self::createRequest()->withAttribute('tree', $tree)->withAttribute('user', $user);
        $middleware = new AuthManager();

        $middleware->process($request, $handler);
    }

    /**
     * @return void
     */
    public function testNotLoggedIn(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(response('lorem ipsum'));

        $tree = $this->createMock(Tree::class);

        $request    = self::createRequest()->withAttribute('tree', $tree)->withAttribute('user', new GuestUser());
        $middleware = new AuthManager();
        $response   = $middleware->process($request, $handler);

        $this->assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
    }
}
