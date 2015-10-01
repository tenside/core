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

/**
 * This class executes a queued task in detached mode.
 */
class ListTasksCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tenside:listtasks')
            ->setDescription('List the queued tasks')
            ->setHelp('You most likely do not want to use this from CLI - use the web UI');
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

        foreach ($taskList->getIds() as $taskId) {
            $task = $taskList->getTask($taskId);
            $output->writeln(sprintf('<info>%s</info> %s (state: %s)', $taskId, $task->getType(), $task->getStatus()));
        }
    }
}
