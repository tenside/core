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

namespace Tenside\Core\Test\EventListener;

use Tenside\CoreBundle\EventListener\TaskListener;
use Tenside\Core\Events\CreateTaskEvent;
use Tenside\Core\Task\InstallTask;
use Tenside\Core\Task\RemovePackageTask;
use Tenside\Core\Task\RequirePackageTask;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\UpgradeTask;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;
use Tenside\CoreBundle\Util\HomePathDeterminator;

/**
 * Test the task listener.
 */
class TaskListenerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->markTestIncomplete();
    }

    /**
     * Test that an InstallTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskInstallTask()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent(new JsonArray([
            InstallTask::SETTING_TYPE            => 'install',
            InstallTask::SETTING_ID              => 'install-task-id',
            InstallTask::SETTING_PACKAGE         => 'vendor/package-name',
            InstallTask::SETTING_VERSION         => '1.0.0',
            InstallTask::SETTING_DESTINATION_DIR => $this->getTempDir(),
            'status'                             => InstallTask::STATE_PENDING
        ]));

        $listener->onCreateTask($event);

        $this->assertTrue($event->hasTask());
        $this->assertInstanceOf(InstallTask::class, $event->getTask());
    }

    /**
     * Test that an UpgradeTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskUpgradeTask()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent(new JsonArray([
            UpgradeTask::SETTING_TYPE            => 'upgrade',
            UpgradeTask::SETTING_ID              => 'upgrade-task-id',
            UpgradeTask::SETTING_HOME            => $this->getTempDir(),
            UpgradeTask::SETTING_PACKAGES        => ['vendor/dependency-name'],
            'status'                             => UpgradeTask::STATE_PENDING,
        ]));

        $listener->onCreateTask($event);

        $this->assertTrue($event->hasTask());
        $this->assertInstanceOf(UpgradeTask::class, $event->getTask());
    }

    /**
     * Test that a RequirePackageTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskRequirePackageTask()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent($config = new JsonArray([
            RequirePackageTask::SETTING_TYPE    => 'require-package',
            RequirePackageTask::SETTING_ID      => 'require-task-id',
            RequirePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
            'status'                            => RequirePackageTask::STATE_PENDING
        ]));

        $listener->onCreateTask($event);

        $this->assertTrue($event->hasTask());
        $this->assertInstanceOf(RequirePackageTask::class, $event->getTask());
        $this->assertEquals($this->getTempDir(), $config->get(RemovePackageTask::SETTING_HOME));
    }

    /**
     * Test that a RemovePackageTask task is created properly.
     *
     * @return void
     */
    public function testOnCreateTaskRemovePackageTask()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent($config = new JsonArray([
            RemovePackageTask::SETTING_TYPE    => 'remove-package',
            RemovePackageTask::SETTING_ID      => 'remove-task-id',
            RemovePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
            'status'                           => RemovePackageTask::STATE_PENDING
        ]));

        $listener->onCreateTask($event);

        $this->assertTrue($event->hasTask());
        $this->assertInstanceOf(RemovePackageTask::class, $event->getTask());
        $this->assertEquals($this->getTempDir(), $config->get(RemovePackageTask::SETTING_HOME));
    }

    /**
     * Test that an unknown task is ignored properly.
     *
     * @return void
     */
    public function testOnCreateTaskWillDoNothingForUnknown()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent(new JsonArray([
            Task::SETTING_TYPE => 'mooh-task',
            'status'           => Task::STATE_PENDING
        ]));

        $listener->onCreateTask($event);

        $this->assertFalse($event->hasTask());
        $this->assertNull($event->getTask());
    }

    /**
     * Test that an present task is not overridden.
     *
     * @return void
     */
    public function testOnCreateWillNotOverrideExisting()
    {
        $home = $this->getMock(HomePathDeterminator::class, ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $listener = new TaskListener($home);

        $event = new CreateTaskEvent(new JsonArray([
            InstallTask::SETTING_TYPE            => 'install',
            InstallTask::SETTING_ID              => 'install-task-id',
            InstallTask::SETTING_PACKAGE         => 'vendor/package-name',
            InstallTask::SETTING_VERSION         => '1.0.0',
            InstallTask::SETTING_DESTINATION_DIR => $this->getTempDir(),
            'status'                             => InstallTask::STATE_PENDING
        ]));

        $event->setTask(
            $mock = $this->getMockBuilder(Task::class)->disableOriginalConstructor()->getMockForAbstractClass()
        );

        $listener->onCreateTask($event);

        $this->assertTrue($event->hasTask());
        $this->assertSame($mock, $event->getTask());
    }
}
