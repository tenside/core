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

namespace Tenside\Core\Task\Composer;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tenside\Core\Task\Composer\WrappedCommand\WrappedCommandTrait;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskOutput;

/**
 * This task provides the basic framework for building tasks that perform composer commands.
 */
abstract class AbstractComposerCommandTask extends Task
{
    /**
     * Prepare the Command instance to execute.
     *
     * @return BaseCommand
     */
    abstract protected function prepareCommand();

    /**
     * Prepare the input interface for the command.
     *
     * @return InputInterface
     */
    abstract protected function prepareInput();

    /**
     * Add missing definition options to the command usually defined by the application.
     *
     * See also composer/composer in src/Composer/Console/Application.php
     *
     * @param BaseCommand $command The command to fix.
     *
     * @return void
     */
    protected function fixCommandDefinition(BaseCommand $command)
    {
        $definition = $command->getDefinition();

        if (!$definition->hasOption('verbose')) {
            $definition->addOption(
                new InputOption(
                    'verbose',
                    'v|vv|vvv',
                    InputOption::VALUE_NONE,
                    'Shows more details including new commits pulled in when updating packages.'
                )
            );
        }
        if (!$definition->hasOption('profile')) {
            $definition->addOption(
                new InputOption(
                    'profile',
                    null,
                    InputOption::VALUE_NONE,
                    'Display timing and memory usage information'
                )
            );
        }
        if (!$definition->hasOption('no-plugins')) {
            $definition->addOption(
                new InputOption(
                    'no-plugins',
                    null,
                    InputOption::VALUE_NONE,
                    'Whether to disable plugins.'
                )
            );
        }
        if (!$definition->hasOption('working-dir')) {
            $definition->addOption(
                new InputOption(
                    'working-dir',
                    '-d',
                    InputOption::VALUE_REQUIRED,
                    'If specified, use the given directory as working directory.'
                )
            );
        }
    }

    /**
     * Attach the composer factory to the command.
     *
     * @param BaseCommand $command The command to patch.
     *
     * @return BaseCommand
     *
     * @throws \InvalidArgumentException When no setComposerFactory method is declared.
     */
    protected function attachComposerFactory(BaseCommand $command)
    {
        if (!method_exists($command, 'setComposerFactory')) {
            throw new \InvalidArgumentException('The passed command does not implement method setComposerFactory()');
        }

        /** @var WrappedCommandTrait $command */
        $command->setComposerFactory(
            function () {
                return Factory::create($this->getIO());
            }
        );

        return $command;
    }

    /**
     * Execute the command and throw exceptions on errors.
     *
     * @param BaseCommand     $command The command to execute.
     *
     * @param InputInterface  $input   The input to use.
     *
     * @param OutputInterface $output The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException On exceptions or when the command has an non zero exit code.
     */
    protected function executeCommand(BaseCommand $command, InputInterface $input, OutputInterface $output)
    {
        try {
            if (0 !== ($statusCode = $command->run($input, $output))) {
                throw new \RuntimeException('Error: command exit code was ' . $statusCode);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function doPerform()
    {
        $command = $this->prepareCommand();
        $command->setIO($this->getIO());
        $this->fixCommandDefinition($command);

        $this->executeCommand($command, $this->prepareInput(), new TaskOutput($this));
    }
}
