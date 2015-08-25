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

use Composer\IO\BufferIO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Config\SourceJson;
use Tenside\Tenside;
use Tenside\Web\Application;
use Tenside\Web\Auth\UserInformation;
use Tenside\Web\Controller\AuthController;

/**
 * Test the composer.json manipulation controller.
 */
class AuthControllerTest extends TestCase
{
    /**
     * Mock the application including tenside and the session.
     *
     * @param UserInformation $userInformation The user information to use.
     *
     * @return Application
     */
    protected function mockApplication(UserInformation $userInformation = null)
    {
        $tensideHome = __DIR__ . '/fixtures';
        chdir($tensideHome);

        $application = $this->getMock('Tenside\\Web\\Application', ['getAuthRegistry'], ['']);
        $tenside     = new Tenside();
        $tenside
            ->setHome($tensideHome)
            ->setConfigSource(new SourceJson($tensideHome . '/tenside.json'))
            ->setInputOutputHandler(new BufferIO());

        $registry = $this->getMockBuilder('Tenside\\Web\\Auth\\AuthRegistry')
            ->setMethods(['handleAuthentication', 'buildChallengeList'])
            ->disableOriginalConstructor()
            ->getMock();

        $registry
            ->expects($this->any())
            ->method('handleAuthentication')
            ->will($this->returnValue($userInformation));
        $registry
            ->expects($this->any())
            ->method('buildChallengeList')
            ->will($this->returnValue(['Tenside realm="test"']));

        $application
            ->expects($this->any())
            ->method('getAuthRegistry')
            ->will($this->returnValue($registry));

        /** @var Application $application */
        $application->setTenside($tenside);

        return $application;
    }

    /**
     * Test retrieval of the composer json.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testGetUnauthenticated()
    {
        $controller = new AuthController();
        $controller->setApplication($this->mockApplication(null));

        $request = new Request(
            [],
            [],
            [
                '_route' => 'checkAuth'
            ],
            [],
            [],
            [
                'PATH_INFO' => '/auth',
                'SCRIPT_NAME' => '/web/app.php',
                'REQUEST_URI' => '/web/app.php/composer.json',
                'QUERY_STRING' => '',
                'REQUEST_METHOD' => 'GET',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SCRIPT_FILENAME' => '/var/www/virtuals/docs/web/app.php',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => __DIR__ . '/fixtures',
                'REMOTE_ADDR' => '1.2.3.4',
                'HTTP_ACCEPT_LANGUAGE' => 'de,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                'CONTENT_TYPE' => 'application/json;charset=UTF-8',
                'HTTP_ORIGIN' => 'http://tenside.tld',
                'HTTP_ACCEPT' => 'application/json, text/plain, */*',
                'HTTP_CONNECTION' => 'close',
                'HTTP_HOST' => 'tenside.tld',
            ],
            null
        );

        $response = $controller->handle($request);
        $this->assertEquals(
            ['status' => 'unauthorized'],
            json_decode($response->getContent(), true)
        );
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test the posting of an auth request.
     *
     * @param UserInformation $data The user data to return from auth providers.
     *
     * @return JsonResponse|Response
     */
    public function handleAuth($data)
    {
        $controller = new AuthController();
        $controller->setApplication($this->mockApplication($data));

        $request = new Request(
            [],
            [],
            [
                '_route' => 'checkAuth'
            ],
            [],
            [],
            [
                'PATH_INFO' => '/auth',
                'SCRIPT_NAME' => '/web/app.php',
                'REQUEST_URI' => '/web/app.php/composer.json',
                'QUERY_STRING' => '',
                'REQUEST_METHOD' => 'GET',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SCRIPT_FILENAME' => '/var/www/virtuals/docs/web/app.php',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => sys_get_temp_dir(),
                'REMOTE_ADDR' => '1.2.3.4',
                'HTTP_ACCEPT_LANGUAGE' => 'de,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                'CONTENT_TYPE' => 'application/json;charset=UTF-8',
                'HTTP_ORIGIN' => 'http://tenside.tld',
                'HTTP_ACCEPT' => 'application/json, text/plain, */*',
                'HTTP_CONNECTION' => 'close',
                'HTTP_HOST' => 'tenside.tld',
            ]
        );

        return $controller->handle($request);
    }

    /**
     * Test posting of a composer.json.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testPostInvalidCredentials()
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
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
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
