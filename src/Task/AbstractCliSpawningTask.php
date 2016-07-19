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

namespace Tenside\Core\Task;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * This class is an abstract base for running a cli command.
 */
abstract class AbstractCliSpawningTask extends Task
{
    /**
     * Run a process and throw exception if it failed.
     *
     * @param Process $process The process to run.
     *
     * @return void
     *
     * @throws ProcessFailedException When the process returned a non zero result.
     */
    protected function runProcess(Process $process)
    {
        $ioHandler = $this->getIO();
        $process->run(
            function ($pipe, $content) use ($ioHandler) {
                if (Process::ERR === $pipe) {
                    $ioHandler->writeError($content, false);
                    // @codingStandardsIgnoreStart
                    return;
                    // @codingStandardsIgnoreEnd
                }
                $ioHandler->write($content, false);
            }
        );

        if (0 !== $process->getExitCode()) {
            throw new ProcessFailedException($process);
        }
    }
}
