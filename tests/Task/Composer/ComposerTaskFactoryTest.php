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

namespace Tenside\Core\Test\Task\Composer;

use Tenside\Core\Task\Composer\ComposerTaskFactory;
use Tenside\Core\Task\Composer\InstallTask;
use Tenside\Core\Task\Composer\RemovePackageTask;
use Tenside\Core\Task\Composer\RequirePackageTask;
use Tenside\Core\Task\Composer\UpgradeTask;
use Tenside\Core\Task\Task;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\HomePathDeterminator;
use Tenside\Core\Util\JsonArray;

/**
 * Test the composer task factory.
 */
class ComposerTaskFactoryTest extends TestCase
{
    /**
     * Provide a list of supported task types.
     *
     * @return array
     */
    public function supportedTaskTypesProvider()
    {
        return [
            [new InstallTask(new JsonArray())],
            [new RequirePackageTask(new JsonArray())],
            [new RemovePackageTask(new JsonArray())],
            [new UpgradeTask(new JsonArray())],
        ];
    }

    /**
     * Test that all known task types are not supported.
     *
     * @param Task $task The task to test.
     *
     * @return void
     *
     * @dataProvider supportedTaskTypesProvider
     */
    public function testDoesSupportKnown(Task $task)
    {
        $factory = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));

        $this->assertTrue(
            $factory->isTypeSupported($task->getType()),
            'Task unsupported in factory: ' . $task->getType()
        );
    }

    /**
     * Test that an unknown task is not supported.
     *
     * @return void
     */
    public function testDoesNotSupportUnknown()
    {
        $factory = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));

        $this->assertFalse($factory->isTypeSupported('unknown-type'));
    }

    /**
     * Test that an unknown task is not supported.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage Do not know how to create task unknown-type
     */
    public function testDoesThrowExceptionWhenCreatingUnknownType()
    {
        $factory = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));
        $factory->createInstance('unknown-type', new JsonArray());
    }

    /**
     * Test that an InstallTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskInstallTask()
    {
        $factory = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));
        $task    = $factory->createInstance(
            'install',
            new JsonArray([
                InstallTask::SETTING_TYPE            => 'install',
                InstallTask::SETTING_ID              => 'install-task-id',
                InstallTask::SETTING_PACKAGE         => 'vendor/package-name',
                InstallTask::SETTING_VERSION         => '1.0.0',
                InstallTask::SETTING_DESTINATION_DIR => $this->getTempDir(),
                'status'                             => InstallTask::STATE_PENDING
            ])
        );

        $this->assertInstanceOf(InstallTask::class, $task);
    }

    /**
     * Test that an UpgradeTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskUpgradeTask()
    {
        $factory = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));
        $task    = $factory->createInstance(
            'upgrade',
            new JsonArray([
                UpgradeTask::SETTING_TYPE            => 'upgrade',
                UpgradeTask::SETTING_ID              => 'upgrade-task-id',
                UpgradeTask::SETTING_HOME            => $this->getTempDir(),
                UpgradeTask::SETTING_PACKAGES        => ['vendor/dependency-name'],
                'status'                             => UpgradeTask::STATE_PENDING,
            ])
        );

        $this->assertInstanceOf(UpgradeTask::class, $task);
    }

    /**
     * Test that a RequirePackageTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskRequirePackageTask()
    {
        $factory    = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));
        $task       = $factory->createInstance(
            'require-package',
            $config = new JsonArray([
                RequirePackageTask::SETTING_TYPE    => 'require-package',
                RequirePackageTask::SETTING_ID      => 'require-task-id',
                RequirePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
                'status'                            => RequirePackageTask::STATE_PENDING
            ])
        );

        $this->assertInstanceOf(RequirePackageTask::class, $task);
        $this->assertEquals($this->getTempDir(), $config->get(RequirePackageTask::SETTING_HOME));
    }

    /**
     * Test that a RemovePackageTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskRemovePackageTask()
    {
        $factory    = new ComposerTaskFactory(new HomePathDeterminator($this->getTempDir()));
        $task       = $factory->createInstance(
            'remove-package',
            $config = new JsonArray([
                RemovePackageTask::SETTING_TYPE    => 'remove-package',
                RemovePackageTask::SETTING_ID      => 'remove-task-id',
                RemovePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
                'status'                           => RemovePackageTask::STATE_PENDING
            ])
        );

        $this->assertInstanceOf(RemovePackageTask::class, $task);
        $this->assertEquals($this->getTempDir(), $config->get(RemovePackageTask::SETTING_HOME));
    }
}
