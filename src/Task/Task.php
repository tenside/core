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

namespace Tenside\Task;

use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Tenside\Tenside;
use Tenside\Util\JsonArray;

/**
 * Abstract base class for tasks.
 */
abstract class Task
{
    /**
     * This state determines that the task is still running.
     */
    const STATE_RUNNING = 'RUNNING';

    /**
     * This state determines that the task has been finished.
     */
    const STATE_FINISHED = 'FINISHED';

    /**
     * The task file to write to.
     *
     * @var JsonArray
     */
    protected $file;

    /**
     * The input/output handler.
     *
     * @var IOInterface
     */
    private $inputOutput;

    /**
     * Task constructor.
     *
     * @param JsonArray $file The json file to write to.
     */
    public function __construct(JsonArray $file)
    {
        $this->file = $file;
    }

    /**
     * Retrieve the task id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->file->get('id');
    }

    /**
     * Retrieve the current output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->file->get('output');
    }

    /**
     * Retrieve the task type name.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Perform the task.
     *
     * @param Tenside $tenside The tenside instance.
     *
     * @return void
     */
    abstract public function perform($tenside);

    /**
     * Add some output.
     *
     * @param string $string The output string to append to the output.
     *
     * @return void
     */
    public function addOutput($string)
    {
        $this->file->set('output', $this->getOutput() . $string);
    }

    /**
     * Retrieve the IO interface.
     *
     * @return IOInterface
     */
    public function getIO()
    {
        if (!isset($this->inputOutput)) {
            $this->inputOutput = new ConsoleIO($this->getInput(), new TaskOutput($this), new HelperSet([]));
        }

        return $this->inputOutput;
    }

    /**
     * Retrieve the current status of a task.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->file->get('status');
    }

    /**
     * Set the task state.
     *
     * @param string $status The status code.
     *
     * @return void
     */
    protected function setStatus($status)
    {
        $this->file->set('status', $status);
    }

    /**
     * Retrieve the Input handler.
     *
     * @return InputInterface
     */
    private function getInput()
    {
        $input = new ArrayInput([]);

        $input->setInteractive(false);

        return $input;
    }
}
