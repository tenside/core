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

namespace Tenside\Core\Test\Util;

use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;
use Tenside\Core\Util\PhpProcessSpawner;

/**
 * Test the RuntimeHelper.
 */
class PhpProcessSpawnerTest extends TestCase
{
    /**
     * Test that the spawner really spawns the process correctly.
     *
     * @return void
     */
    public function testRun()
    {
        $config = new TensideJsonConfig(new JsonArray());
        $config->setPhpCliArguments(['-dmemory_limit=1G']);
        $config->setPhpCliEnvironment(['TESTVAR=TESTVALUE']);

        $process = PhpProcessSpawner::create($config, $this->getTempDir())
            ->spawn(['-r', 'echo getenv(\'TESTVAR\') . ini_get(\'memory_limit\');']);

        $process->run();

        $cli = $process->getCommandLine();
        $this->assertEquals(
            escapeshellarg('php') . ' ' . escapeshellarg('-dmemory_limit=1G') . ' ' .
            escapeshellarg('-r') . ' ' .
            escapeshellarg('echo getenv(\'TESTVAR\') . ini_get(\'memory_limit\');'),
            $cli
        );
        $this->assertEquals(0, $process->getExitCode());
        $this->assertEquals('TESTVALUE1G', $process->getOutput());
        $this->assertEquals('', $process->getErrorOutput());
    }
}
