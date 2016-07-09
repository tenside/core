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
 * @copyright  2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\Test\Composer\Installer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Rule;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Tenside\Core\Composer\Installer\InstallationManager;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the installation manager.
 */
class InstallationManagerTest extends TestCase
{
    /**
     * Test that installations get logged.
     *
     * @return void
     */
    public function testInstall()
    {
        $json      = new JsonArray();
        $manager   = new InstallationManager($json);
        $installer = $this->getMockForAbstractClass(InstallerInterface::class);
        $pool      = $this
            ->getMockBuilder(Pool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->addInstaller($installer);
        $manager->setPool($pool);

        $reason = $this
            ->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reason
            ->method('getPrettyString')
            ->with($pool)
            ->willReturn('The reason is 42');
        $reason
            ->method('getReason')
            ->willReturn(Rule::RULE_PACKAGE_REQUIRES);

        $repository = $this->getMockForAbstractClass(InstalledRepositoryInterface::class);
        $package    = $this->getMockForAbstractClass(PackageInterface::class);
        $operation  = new InstallOperation($package, $reason);

        $package
            ->method('getType')
            ->will($this->returnValue('library'));
        $package
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/package'));

        $installer
            ->expects($this->once())
            ->method('supports')
            ->with('library')
            ->will($this->returnValue(true));

        $installer
            ->expects($this->once())
            ->method('install')
            ->with($repository, $package);

        $manager->install($repository, $operation);

        $this->assertEquals('install', $json->get('vendor\/package/type'));
        $this->assertEquals('The reason is 42', $json->get('vendor\/package/reason'));
    }

    /**
     * Test that updates get logged.
     *
     * @return void
     */
    public function testUpdate()
    {
        $json      = new JsonArray();
        $installer = $this->getMockForAbstractClass(InstallerInterface::class);
        $manager   = new InstallationManager($json);
        $manager->addInstaller($installer);

        $repository = $this->getMockForAbstractClass(InstalledRepositoryInterface::class);
        $package1   = $this->getMockForAbstractClass(PackageInterface::class);
        $package2   = $this->getMockForAbstractClass(PackageInterface::class);
        $operation  = new UpdateOperation($package1, $package2, 'test');

        $package1
            ->method('getType')
            ->will($this->returnValue('library'));
        $package1
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/package'));
        $package2
            ->method('getType')
            ->will($this->returnValue('library'));
        $package2
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/package'));

        $installer
            ->expects($this->once())
            ->method('supports')
            ->with('library')
            ->will($this->returnValue(true));

        $installer
            ->expects($this->once())
            ->method('update')
            ->with($repository, $package1, $package2);

        $manager->update($repository, $operation);

        $this->assertEquals('update', $json->get('vendor\/package/type'));
    }

    /**
     * Test that removals get logged.
     *
     * @return void
     */
    public function testUninstall()
    {
        $json      = new JsonArray();
        $installer = $this->getMockForAbstractClass(InstallerInterface::class);
        $manager   = new InstallationManager($json);
        $manager->addInstaller($installer);

        $repository = $this->getMockForAbstractClass(InstalledRepositoryInterface::class);
        $package    = $this->getMockForAbstractClass(PackageInterface::class);
        $operation  = new UninstallOperation($package, 'test');

        $package
            ->method('getType')
            ->will($this->returnValue('library'));
        $package
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/package'));

        $installer
            ->expects($this->once())
            ->method('supports')
            ->with('library')
            ->will($this->returnValue(true));

        $installer
            ->expects($this->once())
            ->method('uninstall')
            ->with($repository, $package);

        $manager->uninstall($repository, $operation);

        $this->assertEquals('uninstall', $json->get('vendor\/package/type'));
    }

    /**
     * Test that the reason of root requirements get converted.
     *
     * @return void
     */
    public function testRootInstallReasonGetsConverted()
    {
        $json      = new JsonArray();
        $manager   = new InstallationManager($json);
        $installer = $this->getMockForAbstractClass(InstallerInterface::class);
        $pool      = $this
            ->getMockBuilder(Pool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->addInstaller($installer);
        $manager->setPool($pool);

        $reason = $this
            ->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reason
            ->method('getPrettyString')
            ->with($pool)
            ->willReturn('The reason is 42');
        $reason
            ->method('getReason')
            ->willReturn(Rule::RULE_JOB_INSTALL);

        $repository = $this->getMockForAbstractClass(InstalledRepositoryInterface::class);
        $package    = $this->getMockForAbstractClass(PackageInterface::class);
        $operation  = new InstallOperation($package, $reason);

        $package
            ->method('getType')
            ->will($this->returnValue('library'));
        $package
            ->method('getPrettyName')
            ->will($this->returnValue('vendor/package'));

        $installer
            ->expects($this->once())
            ->method('supports')
            ->with('library')
            ->will($this->returnValue(true));

        $installer
            ->expects($this->once())
            ->method('install')
            ->with($repository, $package);
        $manager->install($repository, $operation);

        $this->assertEquals('Required by the root package: The reason is 42', $json->get('vendor\/package/reason'));
    }
}
