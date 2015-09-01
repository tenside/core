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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Test\Web\Controller;

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
     * @return void
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
     *
     * @return void
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
        $this->markTestIncomplete();
        // $controller->createProjectAction($request);
    }
}
