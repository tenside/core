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

namespace Tenside\Test\Task;

use Tenside\Task\InstallTask;
use Tenside\Task\RemovePackageTask;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the remove package task.
 */
class RemovePackageTaskTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        // Redirect composer config and cache into the test temp dir.
        putenv('COMPOSER_HOME=' . $this->getTempDir() . DIRECTORY_SEPARATOR . '.composer');
        $this->createFixture(
            '.composer' . DIRECTORY_SEPARATOR . 'config.json',
            str_replace(
                '##URL##',
                str_replace(
                    '\\',
                    '/',
                    $this->getTempDir() . DIRECTORY_SEPARATOR . 'test-repository' . DIRECTORY_SEPARATOR
                ),
                $this->readFixture('composer' . DIRECTORY_SEPARATOR . 'config.json')
            )
        );

        $this->createFixture(
            'test-repository' . DIRECTORY_SEPARATOR. 'packages.json',
            str_replace(
                '##URL##',
                str_replace(
                    '\\',
                    '/',
                    $this->getTempDir() . DIRECTORY_SEPARATOR . 'test-repository' . DIRECTORY_SEPARATOR
                ),
                $this->readFixture('test-repository' . DIRECTORY_SEPARATOR . 'packages.json')
            )
        );

        $this->provideFixture('test-repository' . DIRECTORY_SEPARATOR. 'vendor-package-name-1.0.0.zip');
        $this->provideFixture('test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name-1.0.0.zip');
        $this->provideFixture('test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name-1.1.0.zip');

        // First we need a proper installation.
        $task = new InstallTask(
            new JsonArray(
                [
                    InstallTask::SETTING_TYPE            => 'install',
                    InstallTask::SETTING_ID              => 'install-task-id',
                    InstallTask::SETTING_PACKAGE         => 'vendor/package-name',
                    InstallTask::SETTING_VERSION         => '1.0.0',
                    InstallTask::SETTING_DESTINATION_DIR => $this->getTempDir(),
                    InstallTask::SETTING_USER            => 'testuser',
                    InstallTask::SETTING_PASSWORD        => 'abc1234',
                ]
            )
        );
        $task->perform($this->getTempFile('logs/install-task.log'));

        if ($task->getStatus() !== InstallTask::STATE_FINISHED) {
            $this->markTestSkipped('Remove package task can not be tested, test installation failed.');
            return;
        }
    }

    /**
     * Test that the base functionality works.
     *
     * @return void
     */
    public function testAll()
    {
        $task = new RemovePackageTask(
            new JsonArray(
                [
                    RemovePackageTask::SETTING_TYPE    => 'remove-package',
                    RemovePackageTask::SETTING_ID      => 'remove-task-id',
                    RemovePackageTask::SETTING_PACKAGE => ['vendor/dependency-name'],
                    RemovePackageTask::SETTING_HOME    => $this->getTempDir(),
                ]
            )
        );

        $task->perform($this->getTempFile('logs/remove-task.log'));

        $this->assertEquals(RemovePackageTask::STATE_FINISHED, $task->getStatus());
        $this->assertContains('Removing vendor/dependency-name (1.0.0)', $task->getOutput());

        $this->assertFileNotExists(
            $this->getTempDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
