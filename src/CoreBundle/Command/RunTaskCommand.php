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

namespace Tenside\CoreBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tenside\Task\Runner;
use Tenside\Util\FunctionAvailabilityCheck;

/**
 * This class executes a queued task in detached mode.
 */
class RunTaskCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tenside:runtask')
            ->setDescription('Execute a queued task')
            ->setHelp('You most likely do not want to use this from CLI - use the web UI')
            ->addArgument('taskId', InputArgument::REQUIRED, 'The task id of the task to run.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When another task is already running.
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        /** @var LoggerInterface $logger */
        $logger = $container->get('logger');

        // If successfully forked, exit now as the parenting process is done.
        if ($this->fork()) {
            $output->writeln('Forked into background.');
            return 0;
        }

        $lock = $container->get('tenside.taskrun_lock');
        $logger->info('Acquire lock file.');

        if (!$lock->lock()) {
            $locked = false;
            $retry  = 3;
            // Try up to 3 times to acquire with short delay in between.
            while ($retry > 0) {
                sleep(1000);
                if ($locked = $lock->lock()) {
                    break;
                }
                $retry--;
            }
            if (!$locked) {
                $logger->error('Could not acquire lock file.');
                throw new \RuntimeException(
                    'Another task appears to be running. If this is not the case, please remove the lock file.'
                );
            }
        }

        try {
            return parent::run($input, $output);
        } finally {
            $logger->info('Release lock file.');
            $lock->release();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When an invalid task id has been passed.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $taskList  = $container->get('tenside.tasks');
        $task      = $taskList->getTask($input->getArgument('taskId'));

        if (!$task) {
            throw new \InvalidArgumentException('Task not found: ' . $input->getArgument('taskId'));
        }

        $runner = new Runner($task);

        try {
            if (!$runner->run(
                $container->get('kernel')->getLogDir() . DIRECTORY_SEPARATOR . 'task-' . $task->getId() . '.log'
            )) {
                $container->get('logger')->error($task->getOutput());

                return 1;
            }
        } catch (\Exception $exception) {
            $container->get('logger')->error($exception->getMessage());
            $container->get('logger')->error($task->getOutput());

            throw $exception;
        }

        return 0;
    }

    /**
     * Try to fork.
     *
     * The return value determines if the caller shall exit (when forking was successful and it is the forking process)
     * or rather proceed execution (is the fork or unable to fork).
     *
     * True means exit, false means go on in this process.
     *
     * @return bool
     *
     * @throws \RuntimeException When the forking caused an error.
     */
    private function fork()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('logger');
        if (!FunctionAvailabilityCheck::isFunctionEnabled('pcntl_fork', 'pcntl')) {
            $logger->warning('pcntl_fork() is not available, execution will block until the command has finished.');

            return false;
        } else {
            $pid = pcntl_fork();
            if (-1 === $pid) {
                throw new \RuntimeException('pcntl_fork() returned -1.');
            } elseif (0 !== $pid) {
                // Tell the calling method to exit now.
                $logger->info('Forked process ' . posix_getpid() . ' to pid ' . $pid);
                return true;
            }

            $logger->info('Processing task in forked process with pid ' . posix_getpid());
            return false;
        }
    }
}
