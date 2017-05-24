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

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * This class runs a task.
 */
class Runner
{
    /**
     * The task to be run.
     *
     * @var Task
     */
    private $task;

    /**
     * The lock handler.
     *
     * @var LockHandler
     */
    private $lock;

    /**
     * The logger in use.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The shutdown error handler shall be executed.
     *
     * @var bool
     */
    private $shutdownHandlerActive;

    /**
     * Create a new instance.
     *
     * @param Task            $task   The task to be run.
     *
     * @param LockHandler     $lock   The lock file to use.
     *
     * @param LoggerInterface $logger The logger to use.
     */
    public function __construct(Task $task, LockHandler $lock, LoggerInterface $logger)
    {
        $this->task   = $task;
        $this->lock   = $lock;
        $this->logger = $logger;
        register_shutdown_function([$this, 'handleError'], $this->task);
    }

    /**
     * Run the task.
     *
     * @param string $logfile The log file to use.
     *
     * @return bool
     */
    public function run($logfile)
    {
        $this->shutdownHandlerActive = true;
        //$this->acquireLock();

        try {
            $this->task->perform($logfile);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->error($this->task->getOutput());
        }

        //$this->releaseLock();

        $this->shutdownHandlerActive = false;

        return Task::STATE_FINISHED === $this->task->getStatus();
    }

    /**
     * Called upon PHP shutdown.
     *
     * @return void
     */
    public function handleError()
    {
        if (!$this->shutdownHandlerActive) {
            return;
        }

        $error = error_get_last();

        if ($error['type'] === E_ERROR) {
            $message = sprintf('Error: "%s" in %s on %s', $error['message'], $error['file'], $error['line']);
            $this->task->markError();
            $this->task->addOutput($message);
            $this->logger->error($message);
        }

        // Ensure to release the lock.
        $this->releaseLock();
    }

    /**
     * Acquire the lock.
     *
     * @return void
     *
     * @throws \RuntimeException When the lock could not be acquired.
     */
    private function acquireLock()
    {
        $this->logger->info('Acquire lock file.');

        if (!$this->lock->lock()) {
            $locked = false;
            $retry  = 3;
            // Try up to 3 times to acquire with short delay in between.
            while ($retry > 0) {
                usleep(1000);
                if ($locked = $this->lock->lock()) {
                    break;
                }
                $retry--;
            }
            if (!$locked) {
                $this->logger->error('Could not acquire lock file.');
                throw new \RuntimeException(
                    'Another task appears to be running. If this is not the case, please remove the lock file.'
                );
            }
        }
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    private function releaseLock()
    {
        $this->logger->info('Release lock file.');
        $this->lock->release();
    }
}
