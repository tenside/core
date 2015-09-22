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
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the install task.
 */
class InstallTaskTest extends TestCase
{
    /**
     * Ensure the contents of a zip file are present in the given dir.
     *
     * @param string $zipFile The source zip to scan.
     *
     * @param string $destDir The directory where the contents shall be checked.
     *
     * @return void
     */
    private function zipHasBeenUnpackedTo($zipFile, $destDir = '')
    {
        $destDir = $this->getTempDir() . DIRECTORY_SEPARATOR . $destDir;

        $zip = new \ZipArchive();
        $zip->open($zipFile);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            $this->assertFileExists($destDir . DIRECTORY_SEPARATOR . $fileName);
            $this->assertEquals(
                stream_get_contents($zip->getStream($fileName)),
                file_get_contents($destDir . DIRECTORY_SEPARATOR . $fileName)
            );
        }
    }

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
                $this->getTempDir(),
                $this->readFixture('composer' . DIRECTORY_SEPARATOR . 'config.json')
            )
        );

        $this->createFixture(
            'test-repository' . DIRECTORY_SEPARATOR. 'packages.json',
            str_replace(
                '##URL##',
                $this->getTempDir(),
                $this->readFixture('test-repository' . DIRECTORY_SEPARATOR . 'packages.json')
            )
        );

        $this->provideFixture($rootZip       = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-package-name.zip');
        $this->provideFixture($dependencyZip = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name.zip');

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

        $this->assertEquals('install-task-id', $task->getId());

        $task->perform();

        $this->assertEquals(InstallTask::STATE_FINISHED, $task->getStatus());

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

        $this->zipHasBeenUnpackedTo($this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $rootZip);
        $this->zipHasBeenUnpackedTo(
            $this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $dependencyZip,
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
