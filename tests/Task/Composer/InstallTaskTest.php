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
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the install task.
 */
class InstallTaskTest extends TestCase
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

        $this->provideFixture(
            $rootZip       = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-package-name-1.0.0.zip'
        );
        $this->provideFixture(
            $dependencyZip = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name-1.0.0.zip'
        );

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

        $task->perform($this->getTempFile('logs/task.log'));

        $this->assertEquals('install-task-id', $task->getId());
        $this->assertEquals(InstallTask::STATE_FINISHED, $task->getStatus());

        // Ensure the temporary directory is gone.
        $this->assertEmpty(glob($this->getTempDir() . DIRECTORY_SEPARATOR . 'install-*'));

        foreach ([
            'vendor/composer/autoload_classmap.php',
            'vendor/composer/ClassLoader.php',
            'vendor/composer/autoload_real.php',
            'vendor/composer/LICENSE',
            'vendor/composer/autoload_psr4.php',
            'vendor/composer/autoload_namespaces.php',
            'vendor/composer/installed.json',
        ] as $file) {
            $this->assertFileExists($this->getTempDir() . DIRECTORY_SEPARATOR . $file);
        }

        $this->assertZipHasBeenUnpackedTo($this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $rootZip);
        $this->assertZipHasBeenUnpackedTo(
            $this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $dependencyZip,
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
