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
 * @author     Yanick Witschi <https://github.com/toflar>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Test\Web\Controller;
use Composer\IO\BufferIO;
use Symfony\Component\HttpFoundation\Request;
use Tenside\Tenside;
use Tenside\Web\Controller\InstallProjectController;


/**
 * Test the create-project command of composers
 */
class InstallProjectControllerTest extends TestCase
{
    /**
     * Tests the create project when already installed.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     */
    public function testAlreadyInstalledException()
    {
        $tenside = $this->getMock('Tenside\\Tenside');
        $tenside->expects($this->any())->method('getHomeDir')->willReturn(__DIR__ . '/fixtures');
        $tenside->expects($this->any())->method('isInstalled')->willReturn(true);

        /** @var Tenside $tenside */
        $controller = new InstallProjectController();
        $controller->setApplication($this->mockDefaultApplication($tenside));

        $request = new Request();

        $controller->createProjectAction($request);
    }

    /**
     * Tests the create project action.
     */
    public function testInstalledProject()
    {
        $tenside = $this->getMock('Tenside\\Tenside');
        $tenside->expects($this->any())->method('getHomeDir')->willReturn(__DIR__ . '/fixtures');
        $tenside->expects($this->any())->method('isInstalled')->willReturn(false);

        /** @var Tenside $tenside */
        $controller = new InstallProjectController();
        $controller->setApplication($this->mockDefaultApplication($tenside));

        $request = new Request([], [], [], [], [], [], json_encode(['project' =>
            [
                'name'      => 'contao/core',
                'version'   => '4.0.0',
            ]
        ]));

        // FIXME: Test this as soon as one can mock the installer
        // $controller->createProjectAction($request);
    }
}
