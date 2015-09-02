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
use Tenside\Web\Controller\AbstractController;

/**
 * Test the composer.json manipulation controller.
 */
class AbstractControllerTest extends TestCase
{
    /**
     * Test that an unknown method action causes a "bad request" response.
     *
     * @return void
     */
    public function testRequestWithUnknownMethod()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\Web\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();
        $controller->expects($this->any())->method('checkAccess')->will($this->returnValue(null));
        /** @var AbstractController $controller */
        $controller->setApplication(
            $this->mockDefaultApplication($this->createDefaultTensideInstance(__DIR__ . '/fixtures'))
        );

        $response = $controller->handle(new Request([], [], ['_route' => 'nonExistantMethod'], [], [], []));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
