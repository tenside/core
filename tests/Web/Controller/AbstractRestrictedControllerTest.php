<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Test\Web\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Web\Auth\AuthRegistry;
use Tenside\Web\Auth\UserInformation;
use Tenside\Web\Controller\AbstractRestrictedController;

/**
 * Test the composer.json manipulation controller.
 */
class AbstractRestrictedControllerTest extends TestCase
{
    /**
     * Test that an authenticated and authorized request will be properly processed.
     *
     * @return void
     */
    public function testRequestWillAuthenticate()
    {
        $authMock = $this->getMockForAbstractClass('Tenside\Web\Auth\AuthInterface');
        $authMock->method('authenticate')->willReturn(
            new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_ALL])
        );
        $application = $this->getMock('Tenside\\Web\\Application', ['getAuthRegistry'], ['/cli/command']);
        $application->method('getAuthRegistry')->willReturn(new AuthRegistry([$authMock]));

        $controller = new DummyRestrictedController(UserInformation::ACL_UPGRADE);

        /** @var AbstractRestrictedController $controller */
        $controller->setApplication($application);

        $response = $controller->handle(new Request([], [], ['_route' => 'getDummy'], [], [], []));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test that an unauthenticated request will throw an exception.
     *
     * @return void
     *
     * @expectedException        \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @expectedExceptionMessage Login required
     */
    public function testUnauthenticatedRequestWillThrowException()
    {
        $authMock = $this->getMockForAbstractClass('Tenside\Web\Auth\AuthInterface');
        $authMock->method('authenticate')->willReturn(null);
        $application = $this->getMock('Tenside\\Web\\Application', ['getAuthRegistry'], ['/cli/command']);
        $application->method('getAuthRegistry')->willReturn(new AuthRegistry([$authMock]));

        $controller = new DummyRestrictedController(UserInformation::ACL_UPGRADE);

        /** @var AbstractRestrictedController $controller */
        $controller->setApplication($application);

        $controller->handle(new Request([], [], ['_route' => 'getDummy'], [], [], []));
    }

    /**
     * Test that an unauthorized request will throw an exception.
     *
     * @return void
     *
     * @expectedException        \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage Insufficient rights.
     */
    public function testUnauthorizedRequestWillThrowException()
    {
        $authMock = $this->getMockForAbstractClass('Tenside\Web\Auth\AuthInterface');
        $authMock->method('authenticate')->willReturn(
            new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_MANIPULATE_REQUIREMENTS])
        );
        $application = $this->getMock('Tenside\\Web\\Application', ['getAuthRegistry'], ['/cli/command']);
        $application->method('getAuthRegistry')->willReturn(new AuthRegistry([$authMock]));

        $controller = new DummyRestrictedController(UserInformation::ACL_UPGRADE);

        /** @var AbstractRestrictedController $controller */
        $controller->setApplication($application);

        $controller->handle(new Request([], [], ['_route' => 'getDummy'], [], [], []));
    }
}
