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

use Tenside\Core\Task\Composer\InstallTask;
use Tenside\Core\Task\Composer\RemovePackageTask;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the remove package task.
 */
class RemovePackageTaskTest extends TestCase
{
    /**
     * Test that the getting of the type name returns the known value.
     *
     * @return void
     */
    public function testGetTypeIsCorrect()
    {
        $task = new RemovePackageTask(
            new JsonArray(
                [
                    RemovePackageTask::SETTING_TYPE    => 'remove-package',
                    RemovePackageTask::SETTING_ID      => 'remove-task-id',
                    RemovePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
                    RemovePackageTask::SETTING_HOME    => $this->getTempDir(),
                    'status'                           => RemovePackageTask::STATE_PENDING
                ]
            )
        );

        $this->assertEquals('remove-package', $task->getType());
    }

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
                    'status'                             => InstallTask::STATE_PENDING
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
                    'status'                           => RemovePackageTask::STATE_PENDING
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

    /**
     * Test that the no-update flag is honored.
     *
     * @return void
     */
    public function testAllWithNoUpdate()
    {
        $task = new RemovePackageTask(
            new JsonArray(
                [
                    RemovePackageTask::SETTING_TYPE      => 'remove-package',
                    RemovePackageTask::SETTING_ID        => 'remove-task-id',
                    RemovePackageTask::SETTING_PACKAGE   => ['vendor/dependency-name'],
                    RemovePackageTask::SETTING_HOME      => $this->getTempDir(),
                    RemovePackageTask::SETTING_NO_UPDATE => true,
                    'status'                             => RemovePackageTask::STATE_PENDING
                ]
            )
        );

        $task->perform($this->getTempFile('logs/remove-task.log'));

        $this->assertEquals(RemovePackageTask::STATE_FINISHED, $task->getStatus());
        $this->assertNotContains('Removing vendor/dependency-name', $task->getOutput());

        $this->assertFileExists(
            $this->getTempDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
