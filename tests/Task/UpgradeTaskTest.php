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

namespace Tenside\Core\Test\Task;

use Tenside\Core\Task\InstallTask;
use Tenside\Core\Task\UpgradeTask;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;
use Tenside\Core\Util\JsonFile;

/**
 * This class tests the install task.
 */
class UpgradeTaskTest extends TestCase
{
    /**
     * Test that the base functionality works.
     *
     * @return void
     */
    public function testAll()
    {
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
        $this->provideFixture($newZip = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name-1.1.0.zip');

        // First we need a proper installation.
        $task = new InstallTask(
            new JsonArray(
                [
                    InstallTask::SETTING_TYPE            => 'install',
                    InstallTask::SETTING_ID              => 'install-task-id',
                    InstallTask::SETTING_PACKAGE         => 'vendor/package-name',
                    InstallTask::SETTING_VERSION         => '1.0.0',
                    InstallTask::SETTING_DESTINATION_DIR => $this->getTempDir(),
                    'status'                             => InstallTask::STATE_PENDING,
                ]
            )
        );
        $task->perform($this->getTempFile('logs/install-task.log'));

        if ($task->getStatus() !== InstallTask::STATE_FINISHED) {
            $this->markTestSkipped('Upgrade task can not be tested, test installation failed.');
            return;
        }

        // Now the upgrade.
        $file = new JsonFile($this->getTempDir() . DIRECTORY_SEPARATOR . 'composer.json');
        $file->set('repositories', ['packagist' => false]);
        $file->set('require/' . $file->escape('vendor/dependency-name'), '~1.0');
        unset($file);

        $task = new UpgradeTask(
            new JsonArray(
                [
                    UpgradeTask::SETTING_TYPE            => 'upgrade',
                    UpgradeTask::SETTING_ID              => 'upgrade-task-id',
                    UpgradeTask::SETTING_HOME            => $this->getTempDir(),
                    UpgradeTask::SETTING_PACKAGES        => ['vendor/dependency-name'],
                    'status'                             => UpgradeTask::STATE_PENDING,
                ]
            )
        );

        $task->perform($this->getTempFile('logs/task.log'));

        $this->assertEquals('upgrade-task-id', $task->getId());
        $this->assertEquals(UpgradeTask::STATE_FINISHED, $task->getStatus());

        $this->assertContains('Installing vendor/dependency-name (1.1.0)', $task->getOutput());

        $this->assertZipHasBeenUnpackedTo(
            $this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $newZip,
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
