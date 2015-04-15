<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Test\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Web\Controller\AuthController;

/**
 * Test the composer.json manipulation controller.
 */
class AuthControllerTest extends TestCase
{
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
        $controller->setApplication($this->mockApplication(__DIR__ . '/fixtures'));

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

        unset($_SESSION);
        $response = $controller->handle($request);
        $this->assertEquals(
            ['id' => $controller->getApplication()->getSession()->getId()],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Test the posting of an auth request.
     *
     * @param string $data      The composer.json content.
     *
     * @param string $sessionId The session id to use.
     *
     * @return JsonResponse|Response
     */
    public function handlePostData($data, $sessionId)
    {
        $controller = new AuthController();
        $controller->setApplication($this->mockApplication(null, $sessionId));

        $request = new Request(
            [],
            [],
            [
                '_route' => 'validateLogin'
            ],
            [],
            [],
            [
                'PATH_INFO' => '/auth',
                'SCRIPT_NAME' => '/web/app.php',
                'REQUEST_URI' => '/web/app.php/composer.json',
                'QUERY_STRING' => '',
                'REQUEST_METHOD' => 'POST',
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
            ],
            json_encode($data)
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
        unset($_SESSION);
        $response = $this->handlePostData(
            [
                'username' => 'foo',
                'password' => 'bar',
            ],
            'ABC'
        );

        $this->assertEquals(
            ['id' => 'ABC'],
            json_decode($response->getContent(), true)
        );
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
        unset($_SESSION);
        $response = $this->handlePostData(
            [
                'username' => '',
                'password' => '',
            ],
            'ABCzzzz'
        );

        $this->assertEquals(
            ['id' => 'ABCzzzz'],
            json_decode($response->getContent(), true)
        );
    }
}
