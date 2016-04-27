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
 * This class tests that the php-cli can fork sub processes.
 */
class SelfTestCliCanFork extends AbstractSelfTest
{
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
        $this->setMessage('Check if the PHP CLI executable can fork processes.');

        if (null === ($this->interpreter = $this->getAutoConfig()->getPhpCliBinary())) {
            $this->markSkipped('No PHP interpreter detected, can not test - assuming forking is not supported.');
            $this->getAutoConfig()->setForkingAvailable(false);
            return;
        }

        $output = $this->testCliRuntime('var_export(function_exists("pcntl_fork"));');
        if ('true' !== $output) {
            $this->markFailed('Function pcntl_fork not available (Output: ' . $output . ').');
            $this->getAutoConfig()->setForkingAvailable(false);
            return;
        }

        $output = $this->testCliRuntime('var_export(pcntl_fork());');
        if (is_numeric($output)) {
            if ('-1' === $output) {
                $this->markFailed('Function pcntl_fork() returned -1. Forking is not supported.');
                $this->getAutoConfig()->setForkingAvailable(false);
                return;
            }
            $this->markSuccess('Forked as pid ' . $output);
            $this->getAutoConfig()->setForkingAvailable(true);
            return;
        }

        $this->getAutoConfig()->setForkingAvailable(false);

        $this->markWarning(
            'Could not determine if forking is possible, assuming not supported (Output : ' . $output .').'
        );
    }

    /**
     * Runs the passed test script through the php cli and returns the output.
     *
     * @param string $testScript The test script to run.
     *
     * @return null|string
     */
    private function testCliRuntime($testScript)
    {
        $process = new Process(
            sprintf('%s %s', escapeshellcmd($this->interpreter), escapeshellarg('-r ' . $testScript))
        );

        if (0 !== $process->run()) {
            return null;
        }

        return $process->getOutput();
    }
}
