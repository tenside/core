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

namespace Tenside\SelfTest\Cli;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Tenside\SelfTest\AbstractSelfTest;

/**
 * This class tests that a valid php-cli binary is available.
 */
class SelfTestCliArguments extends AbstractSelfTest
{
    /**
     * The output buffer to keep track of the detection.
     *
     * @var BufferedOutput
     */
    private $log;

    /**
     * The interpreter to use.
     *
     * @var string
     */
    private $interpreter;

    /**
     * Check that we have a correct CLI executable of PHP.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check which arguments to pass to the PHP CLI executable.');

        if (null === ($this->interpreter = $this->getAutoConfig()->getPhpInterpreter())) {
            $this->markFailed('No PHP interpreter detected, can not test.');
            return;
        }

        $this->log = new BufferedOutput();

        if ($this->check()) {
            $data = $this->log->fetch();
            if (empty($data)) {
                $data = 'No special arguments needed.';
            }
            $this->markSuccess($data);
            return;
        }

        $this->markWarning(
            'Could not determine command line arguments, leaving unconfigured and hope the best.'
        );
    }

    /**
     * Check the needed parameters.
     *
     * @return bool
     */
    private function check()
    {
        return
            $this->testMemoryLimit()
            && $this->testMaxExecutionTime();
    }

    /**
     * Test if raising the memory limit is needed.
     *
     * @return bool
     */
    private function testMemoryLimit()
    {
        $output = $this->testCliRuntime('echo ini_get(\'memory_limit\');');
        if ('-1' !== $output) {
            if ($this->testOverride('echo ini_get(\'memory_limit\');', '-d memory_limit=1G', '1G')) {
                $this->getAutoConfig()->addCommandLineArgument('-d memory_limit=1G');
                $this->log->writeln('Will override memory_limit of ' . $output . ' with 1G.');
            }
        }

        return true;
    }

    /**
     * Test if raising the memory limit is needed.
     *
     * @return bool
     */
    private function testMaxExecutionTime()
    {
        $output = $this->testCliRuntime('echo ini_get(\'max_execution_time\');');
        if (900 > intval($output)) {
            if ($this->testOverride('echo ini_get(\'max_execution_time\');', '-d max_execution_time=900', '900')) {
                $this->getAutoConfig()->addCommandLineArgument('-d max_execution_time=900');
                $this->log->writeln('Will override max_execution_time of ' . $output . ' with 900 seconds.');
            }
        }

        return true;
    }

    /**
     * Test if overriding a parameter works.
     *
     * @param string $script        The script to run.
     *
     * @param string $definition    The argument to pass.
     *
     * @param string $expectedValue The expected output value.
     *
     * @return bool
     */
    private function testOverride($script, $definition, $expectedValue)
    {
        $output = $this->testCliRuntime($script, $definition);
        if ($expectedValue !== $output) {
            $this->log->writeln('Could not override via ' . $definition);
            return false;
        }

        return true;
    }

    /**
     * Runs the passed test script through the php cli and returns the output.
     *
     * @param string $testScript The test script to run.
     *
     * @param string $definition Optional definition to override.
     *
     * @return null|string
     */
    private function testCliRuntime($testScript, $definition = '')
    {
        if ($definition) {
            $definition = escapeshellarg($definition);
        }

        $process = new Process(
            sprintf(
                '%s %s %s',
                escapeshellcmd($this->interpreter),
                $definition,
                escapeshellarg('-r ' . $testScript)
            )
        );

        if (0 !== $process->run()) {
            return null;
        }

        return $process->getOutput();
    }
}
