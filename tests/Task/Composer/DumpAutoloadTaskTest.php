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

use Tenside\Core\Task\Composer\DumpAutoloadTask;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the dumpautoload task.
 */
class DumpAutoloadTaskTest extends TestCase
{
    /**
     * Test the the autoload file is dumped
     */
    public function testAutoloadIsDumped()
    {
        $composerJson = [
            'autoload' => [
                'psr-4' => [
                    'Foo\\Bar\\' => 'src'
                ]
            ]
        ];
        $dummyClass = '<?php namespace Foo\Bar; class Dummy {}';

        $this->createFixture('composer.json', json_encode($composerJson));
        $this->createFixture('/src/DummyClass.php', $dummyClass);

        $task = new DumpAutoloadTask(new JsonArray(
            [
                DumpAutoloadTask::SETTING_TYPE   => 'dumpautoload',
                DumpAutoloadTask::SETTING_ID     => 'dumpautoload-task-id',
                'status'                         => DumpAutoloadTask::STATE_PENDING,
                DumpAutoloadTask::SETTING_HOME   => $this->getTempDir(),
            ]
        ));

        $task->perform($this->getTempFile('logs/task.log'));

        $this->assertFileExists($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/autoload.php');
        $this->assertFileExists($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_psr4.php');

        $psr4FileContent = file_get_contents($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_psr4.php');
        $classMapContent = file_get_contents($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_classmap.php');

        $this->assertContains("'Foo\\\\Bar\\\\' => array(\$baseDir . '/src')", $psr4FileContent);

        // Did not optimize so MUST NOT contain
        $this->assertNotContains("'Foo\\\\Bar\\\\Dummy' => \$baseDir . '/src/DummyClass.php'", $classMapContent);
    }

    /**
     * Tests if the --optimize option is executed
     */
    public function testAutoloadIsDumpedWithOptimizeOption()
    {

        $composerJson = [
            'autoload' => [
                'psr-4' => [
                    'Foo\\Bar\\' => 'src'
                ]
            ]
        ];
        $dummyClass = '<?php namespace Foo\Bar; class Dummy {}';

        $this->createFixture('composer.json', json_encode($composerJson));
        $this->createFixture('/src/DummyClass.php', $dummyClass);

        $task = new DumpAutoloadTask(new JsonArray(
            [
                DumpAutoloadTask::SETTING_TYPE      => 'dumpautoload',
                DumpAutoloadTask::SETTING_ID        => 'dumpautoload-task-id',
                'status'                            => DumpAutoloadTask::STATE_PENDING,
                DumpAutoloadTask::SETTING_HOME      => $this->getTempDir(),
                DumpAutoloadTask::SETTING_OPTIMIZE  => true, // Key for this test
            ]
        ));

        $task->perform($this->getTempFile('logs/task.log'));

        $this->assertFileExists($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/autoload.php');
        $this->assertFileExists($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_psr4.php');

        $psr4FileContent = file_get_contents($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_psr4.php');
        $classMapContent = file_get_contents($this->getTempDir() . DIRECTORY_SEPARATOR . '/vendor/composer/autoload_classmap.php');

        $this->assertContains("'Foo\\\\Bar\\\\' => array(\$baseDir . '/src')", $psr4FileContent);

        // Did not optimize so MUST contain
        $this->assertContains("'Foo\\\\Bar\\\\Dummy' => \$baseDir . '/src/DummyClass.php'", $classMapContent);
    }
}
