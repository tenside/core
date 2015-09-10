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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\CoreBundle\Controller\AppKernelController;

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
        $this->provideFixture('app/AppKernel.php');

        $controller = new AppKernelController();
        $controller->setContainer($this->createDefaultContainer());
        $response = $controller->getAppKernelAction();

        $this->assertEquals(
            file_get_contents(
                $this->getFixturesDirectory() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AppKernel.php'
            ),
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
        touch($this->getTempFile('app' . DIRECTORY_SEPARATOR . 'AppKernel.php'));

        $controller = new AppKernelController();
        $controller->setContainer($this->createDefaultContainer());

        if ('' !== $phpCli) {
            // Override tenside configuration.
            $controller->getTensideConfig()->set('php-cli', $phpCli);
        }

        $request = new Request([], [], [], [], [], [], $data);

        return $controller->putAppKernelAction($request);
    }

    /**
     * Test posting of a AppKernel.
     *
     * @return void
     */
    public function testPost()
    {
        $response = $this->handlePostData(file_get_contents($this->getFixturesDirectory() . '/app/AppKernel.php'));

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
