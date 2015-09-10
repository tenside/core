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

namespace Tenside\Test\CoreBundle\Controller;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Tenside\CoreBundle\Controller\AuthController;
use Tenside\CoreBundle\Security\UserInformation;

/**
 * Test the composer.json manipulation controller.
 */
class AuthControllerTest extends TestCase
{
    /**
     * Build the container for authentication.
     *
     * @return Container
     */
    private function buildContainer()
    {
        $jwtAuth = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Security\\JWTAuthenticator')
            ->setMethods(['getTokenForData'])
            ->disableOriginalConstructor()
            ->getMock();

        $jwtAuth->method('getTokenForData')->willReturn('Auth-Token');

        $container = $this->createDefaultContainer(
            [
                'security.token_storage' => new TokenStorage(),
                'tenside.jwt_authenticator' => $jwtAuth
            ]
        );

        return $container;
    }

    /**
     * Test the posting of an auth request.
     *
     * @param UserInformation $data The user data to return from auth providers.
     *
     * @return JsonResponse|Response
     */
    private function handleAuth($data)
    {
        $controller = $this->getMock('Tenside\\CoreBundle\\Controller\\AuthController', ['getUser']);
        $controller->method('getUser')->willReturn($data);
        /** @var AuthController $controller */
        $controller->setContainer($this->buildContainer());

        return $controller->checkAuthAction();
    }

    /**
     * Test retrieval of the composer json.
     *
     * @return void
     */
    public function testGetUnauthenticated()
    {
        $response = $this->handleAuth(null);

        $this->assertEquals(
            ['status' => 'unauthorized'],
            json_decode($response->getContent(), true)
        );
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test posting of a composer.json that contains a warning.
     *
     * @return void
     */
    public function testPostValidCredentials()
    {
        $response = $this->handleAuth(new UserInformation(['acl' => 7]));

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('acl', $result);
        $this->assertEquals('ok', $result['status']);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
