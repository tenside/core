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

namespace Tenside\Core\SelfTest\Cli;

use Symfony\Component\Process\Process;
use Tenside\Core\SelfTest\AbstractSelfTest;

/**
 * This class tests that a valid php-cli binary is available.
 */
abstract class AbstractSelfTestCli extends AbstractSelfTest
{
    /**
     * The interpreter to use.
     *
     * @var string
     */
    private $interpreter;

    /**
     * Check that the interpreter is available.
     *
     * @return bool
     */
    protected function hasInterpreter()
    {
        return (null !== ($this->interpreter = $this->getAutoConfig()->getPhpCliBinary()));
    }

    /**
     * Runs the passed test script through the php cli and returns the output.
     *
     * @param string $testScript The test script to run.
     *
     * @param string $arguments  Optional cli arguments to use.
     *
     * @return null|string
     */
    protected function testCliRuntime($testScript, $arguments = '')
    {
        if ($arguments) {
            $arguments = escapeshellarg($arguments);
        }

        $process = new Process(
            sprintf(
                '%s %s %s',
                escapeshellcmd($this->interpreter),
                $arguments,
                escapeshellarg('-r ' . $testScript)
            )
        );

        if (0 !== $process->run()) {
            return null;
        }

        return $process->getOutput();
    }
}
