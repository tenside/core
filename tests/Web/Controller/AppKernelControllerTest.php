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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Web\Controller\ComposerJsonController;

/**
 * Test the AppKernel manipulation controller.
 */
class AppKernelControllerTest extends TestCase
{
    /**
     * Test retrieval of the AppKernel.
     *
     * @return void
     */
    public function testGet()
    {
        $controller = $this->getMock('Tenside\\Web\\Controller\\AppKernelController', ['needAccessLevel']);
        $controller->expects($this->any())->method('checkAccess')->will($this->returnValue(null));
        /** @var ComposerJsonController $controller */
        $controller->setApplication(
            $this->mockDefaultApplication($this->createDefaultTensideInstance(__DIR__ . '/fixtures'))
        );

        $request  = new Request([], [], ['_route' => 'getAppKernel']);
        $response = $controller->handle($request);

        $this->assertEquals(
            file_get_contents(__DIR__ . '/fixtures/app/AppKernel.php'),
            $response->getContent()
        );
    }

    /**
     * Test the posting of a AppKernel file.
     *
     * @param string $data   The AppKernel content.
     *
     * @param string $phpCli The path to the php CLI binary to override.
     *
     * @return JsonResponse|Response
     */
    public function handlePostData($data, $phpCli = '')
    {
        chdir($this->getTempDir());
        mkdir($this->getTempDir() . DIRECTORY_SEPARATOR . 'app');
        touch($this->getTempDir() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AppKernel.php');

        $controller = $this->getMock('Tenside\\Web\\Controller\\AppKernelController', ['needAccessLevel']);
        $controller->expects($this->any())->method('checkAccess')->will($this->returnValue(null));
        /** @var ComposerJsonController $controller */
        $controller->setApplication(
            $this->mockDefaultApplication($this->createDefaultTensideInstance($this->getTempDir()))
        );

        if ('' !== $phpCli) {
            // Override tenside configuration.
            $controller->getTenside()->getConfigSource()->set('php-cli', $phpCli);
        }

        $request = new Request([], [], ['_route' => 'putAppKernel'], [], [], [], $data);

        return $controller->handle($request);
    }

    /**
     * Test posting of a AppKernel.
     *
     * @return void
     */
    public function testPost()
    {
        $response = $this->handlePostData(file_get_contents(__DIR__ . '/fixtures/app/AppKernel.php'));

        $result = json_decode($response->getContent(), true);

        $this->assertEmpty($result['error']);
        $this->assertEquals('OK', $result['status']);
    }

    /**
     * Test posting of a AppKernel containing errors.
     *
     * @return void
     */
    public function testPostWithError()
    {
        $response = $this->handlePostData(<<<EOF
<?php
invalid syntax causes parse error
EOF
        );

        $result = json_decode($response->getContent(), true);
        $this->assertNotEmpty($result['error']);
        $this->assertEquals('ERROR', $result['status']);
        $this->assertEquals('2', $result['error']['line']);
    }

    /**
     * Test posting of a AppKernel containing errors.
     *
     * @return void
     */
    public function testPostWithUnknownError()
    {
        $content  = <<<EOF
EOF;
        $response = $this->handlePostData($content, 'invalid-path-to-php-cli');

        $result = json_decode($response->getContent(), true);
        $this->assertNotEmpty($result['error']);
        $this->assertEquals('ERROR', $result['status']);
        $this->assertEquals('0', $result['error']['line']);
    }
}
