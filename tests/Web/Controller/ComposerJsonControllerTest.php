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

use Composer\IO\BufferIO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Config\SourceJson;
use Tenside\Tenside;
use Tenside\Web\Application;
use Tenside\Web\Controller\ComposerJsonController;

/**
 * Test the composer.json manipulation controller.
 */
class ComposerJsonControllerTest extends TestCase
{
    /**
     * Mock the application including tenside and the session.
     *
     * @param null|string $tensideHome The home dir to use.
     *
     * @return Application
     */
    protected function mockApplication($tensideHome = null)
    {
        if (null === $tensideHome) {
            $tensideHome = sys_get_temp_dir();
        }
        chdir($tensideHome);

        $application = $this->getMock('Tenside\\Web\\Application', null);
        $tenside     = new Tenside();
        $tenside
            ->setHome($tensideHome)
            ->setConfigSource(new SourceJson($tensideHome . '/tenside.json'))
            ->setInputOutputHandler(new BufferIO());

        /** @var Application $application */
        $application->setTenside($tenside);

        return $application;
    }

    /**
     * Test retrieval of the composer json.
     *
     * @return void
     */
    public function testGet()
    {
        $controller = $this->getMock('Tenside\\Web\\Controller\\ComposerJsonController', ['needAccessLevel']);
        $controller->expects($this->any())->method('checkAccess')->will($this->returnValue(null));
        /** @var ComposerJsonController $controller */
        $controller->setApplication($this->mockApplication(__DIR__ . '/fixtures'));

        $request = new Request(
            [],
            [],
            [
                '_route' => 'getComposerJson'
            ],
            [],
            [],
            [
                'PATH_INFO' => '/composer.json',
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
            json_decode(file_get_contents(__DIR__ . '/fixtures/composer.json'), true),
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Test the posting of a composer.json file.
     *
     * @param string $data The composer.json content.
     *
     * @return JsonResponse|Response
     */
    public function handlePostData($data)
    {
        chdir(sys_get_temp_dir());

        $controller = $this->getMock('Tenside\\Web\\Controller\\ComposerJsonController', ['needAccessLevel']);
        $controller->expects($this->any())->method('checkAccess')->will($this->returnValue(null));
        /** @var ComposerJsonController $controller */
        $controller->setApplication($this->mockApplication());

        $request = new Request(
            [],
            [],
            [
                '_route' => 'putComposerJson'
            ],
            [],
            [],
            [
                'PATH_INFO' => '/composer.json',
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
     */
    public function testPost()
    {
        $response = $this->handlePostData(
            [
                'name'        => 'some/website',
                'description' => 'some description',
                'license'     => 'MIT'
            ]
        );

        $result = json_decode($response->getContent(), true);

        $this->assertEmpty($result['warning']);
        $this->assertEmpty($result['error']);
        $this->assertEquals('OK', $result['status']);
    }

    /**
     * Test posting of a composer.json that contains a warning.
     *
     * @return void
     */
    public function testPostWithWarning()
    {
        $response = $this->handlePostData(
            [
                'name'        => 'some/website',
                'description' => 'some description',
            ]
        );

        $result = json_decode($response->getContent(), true);

        $this->assertNotEmpty($result['warning']);
        $this->assertEquals('OK', $result['status']);
    }

    /**
     * Test posting of a composer.json containing errors.
     *
     * @return void
     */
    public function testPostWithError()
    {
        $response = $this->handlePostData(
            [
                'description' => 'some description',
                'license'     => 'MIT'
            ]
        );

        $result = json_decode($response->getContent(), true);

        $this->assertEmpty($result['warning']);
        $this->assertNotEmpty($result['error']);
        $this->assertEquals('ERROR', $result['status']);
    }
}
