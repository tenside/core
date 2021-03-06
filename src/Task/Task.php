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

use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Tenside\Core\Util\JsonArray;

/**
 * Abstract base class for tasks.
 */
abstract class Task
{
    /**
     * The type of the task.
     */
    const SETTING_TYPE = 'type';

    /**
     * The id of the task.
     */
    const SETTING_ID = 'id';

    /**
     * The ISO 8601 date when the task got created.
     */
    const SETTING_CREATED_AT = 'created-at';

    /**
     * The user data.
     */
    const SETTING_USER_DATA = 'user-data';

    /**
     * This state determines that the task is still awaiting to be executed.
     */
    const STATE_PENDING = 'PENDING';

    /**
     * This state determines that the task is still running.
     */
    const STATE_RUNNING = 'RUNNING';

    /**
     * This state determines that the task has been finished.
     */
    const STATE_FINISHED = 'FINISHED';

    /**
     * This state determines that the task has been finished with errors.
     */
    const STATE_ERROR = 'ERROR';

    /**
     * The task file to write to.
     *
     * @var JsonArray
     */
    protected $file;

    /**
     * The log file to write to.
     *
     * @var string
     */
    protected $logFile;

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

        if ($this->file->has('log')) {
            $this->logFile = $this->file->get('log');
        }
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
     * @param null|int $offset The offset in bytes to read from.
     *
     * @return string
     */
    public function getOutput($offset = null)
    {
        if (!$this->logFile) {
            return '';
        }

        return (string) file_get_contents($this->logFile, FILE_BINARY, null, $offset);
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
     * @param string $logFile The log file to write to.
     *
     * @return void
     *
     * @throws \LogicException   When the task has already been run.
     *
     * @throws \RuntimeException When the execution. failed.
     */
    public function perform($logFile)
    {
        if (self::STATE_PENDING !== $this->getStatus()) {
            throw new \LogicException('Attempted to run task ' . $this->getId() . ' twice.');
        }

        try {
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0777, true);
            }

            file_put_contents($logFile, '', FILE_BINARY);

            $this->logFile = $logFile;
            $this->file->set('log', $logFile);

            $this->setStatus(self::STATE_RUNNING);
            $this->addOutput('Task ' . $this->getId() . ' started.' . "\n");

            $this->doPerform();
        } catch (\Exception $exception) {
            $this->addOutput('--------------------------------------------------------' . "\n");
            $this->addOutput('Exception occured: ' . $exception->getMessage() . "\n");
            $this->addOutput($exception->getTraceAsString() . "\n");
            $loopException = $exception;
            while ($loopException = $loopException->getPrevious()) {
                $this->addOutput('Chained exception: ' . $loopException->getMessage() . "\n");
                $this->addOutput($loopException->getTraceAsString() . "\n");
            }
            $this->addOutput('--------------------------------------------------------' . "\n");

            $this->setStatus(self::STATE_ERROR);

            throw new \RuntimeException(
                'Task ' . $this->getId() . ' errored: ' . $exception->getMessage(),
                1,
                $exception
            );
        }

        $this->addOutput('Finished without error.' . "\n");
        $this->setStatus(self::STATE_FINISHED);
    }

    /**
     * Perform the task.
     *
     * @return void
     */
    abstract public function doPerform();

    /**
     * Add some output.
     *
     * @param string $string The output string to append to the output.
     *
     * @return void
     *
     * @throws \LogicException When called prior to perform().
     */
    public function addOutput($string)
    {
        if (!$this->logFile) {
            throw new \LogicException('The has not started to run yet.');
        }

        file_put_contents($this->logFile, $string, (FILE_APPEND | FILE_BINARY));
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
     * Retrieve when this task got created as ISO 8601 date string.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return \DateTime::createFromFormat(DATE_ISO8601, $this->file->get(self::SETTING_CREATED_AT));
    }

    /**
     * Retrieve the user submitted payload.
     *
     * @return array
     */
    public function getUserData()
    {
        return $this->file->get(self::SETTING_USER_DATA);
    }

    /**
     * Remove the attached files for this task from disk.
     *
     * @return void
     */
    public function removeAssets()
    {
        // Base implementation does only know about log file.
        if ($this->logFile) {
            if (file_exists($this->logFile)) {
                unlink($this->logFile);
            }
            $this->logFile = null;
            $this->file->remove('log');
        }
    }

    /**
     * Mark the task errored.
     *
     * @return void
     */
    public function markError()
    {
        $this->setStatus(self::STATE_ERROR);
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
