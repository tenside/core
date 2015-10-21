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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Tenside\Task\Runner;

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
        $lockDir = $container->get('tenside.home')->tensideDataDir();
        $lock    = new LockHandler('task-run', $lockDir);
        $logger->info('Acquire lock file.');

        if (!$lock->lock()) {
            $logger->error('Could not acquire lock file.');
            throw new \RuntimeException(
                'Another task appears to be running. ' .
                'If this is not the case, please remove the lock file in ' .
                $lockDir
            );
        }

        try {
            return parent::run($input, $output);
        } finally {
            $logger->info('Release lock file.');
            $lock->release();
        }
    }
     * @throws \InvalidArgumentException When an invalid task id has been passed.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $task      = $container->get('tenside.tasks')->getTask($input->getArgument('taskId'));

        if (!$task) {
            throw new \InvalidArgumentException('Task not found: ' . $input->getArgument('taskId'));
        }

        $runner = new Runner($task);

        if (!$runner->run(
            $this->getContainer()->get('kernel')->getLogDir() . DIRECTORY_SEPARATOR . 'task-' . $task->getId() . '.log'
        )) {
            return 1;
        }

        return 0;
    }
}
