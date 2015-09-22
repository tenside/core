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

namespace Tenside\Console;

use Composer\Command\ScriptAliasCommand;
use Composer\Util\ErrorHandler;
use Symfony\Bundle\FrameworkBundle\Console\Shell;
use Symfony\Component\Console\Application as SymfonyApplication;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Composer\Factory as ComposerFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Tenside\Composer\ComposerJson;
use Tenside\Tenside;
use Tenside\Util\RuntimeHelper;

/**
 * The console application that handles the commands.
 */
class Application extends SymfonyApplication
{
    /**
     * The kernel in use.
     *
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Flag if commands have been registered.
     *
     * @var bool
     */
    private $commandsRegistered = false;

    /**
     * Out logo, will get concatenated with the composer logo.
     *
     * @var string
     */
    private static $logo = '
 _____               _     _
/__   \___ _ __  ___(_) __| | ___
  / /\/ _ \ \'_ \/ __| |/ _` |/ _ \
 / / |  __/ | | \__ \ | (_| |  __/
 \/   \___|_| |_|___/_|\__,_|\___|
';

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel A KernelInterface instance.
     */
    public function __construct(KernelInterface $kernel)
    {
        if (function_exists('ini_set') && extension_loaded('xdebug')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(date_default_timezone_get());
        }

        $this->kernel = $kernel;

        parent::__construct(
            'Tenside',
            Tenside::VERSION . ' (symfony ' . Kernel::VERSION . ') ' .
            ' - ' . $kernel->getName() . '/' . $kernel->getEnvironment() . ($kernel->isDebug() ? '/debug' : '')
        );

        $definition = $this->getDefinition();
        $definition->addOption(new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.'));
        $definition->addOption(
            new InputOption(
                '--process-isolation',
                null,
                InputOption::VALUE_NONE,
                'Launch commands from shell as a separate process.'
            )
        );
        if ('phar' !== $kernel->getEnvironment()) {
            $definition->addOption(
                new InputOption(
                    '--env',
                    '-e',
                    InputOption::VALUE_REQUIRED,
                    'The Environment name.',
                    $kernel->getEnvironment()
                )
            );

            $definition->addOption(
                new InputOption(
                    '--no-debug',
                    null,
                    InputOption::VALUE_NONE,
                    'Switches off debug mode.'
                )
            );
        }
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface A KernelInterface instance
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->boot();

        if (!$this->commandsRegistered) {
            $this->registerCommands($output);

            $this->commandsRegistered = true;
        }

        $container = $this->kernel->getContainer();

        foreach ($this->all() as $command) {
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        $this->setDispatcher($container->get('event_dispatcher'));

        if (true === $input->hasParameterOption(array('--shell', '-s'))) {
            $shell = new Shell($this);
            $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
            $shell->run();

            return 0;
        }

        // FIXME: this is broken now.
        RuntimeHelper::setupHome($container->get('tenside.home')->homeDir());

        $this->io = new ConsoleIO($input, $output, $this->getHelperSet());

        if (version_compare(PHP_VERSION, '5.4', '<')) {
            $output->writeln(
                '<warning>Tenside only officially supports PHP 5.4 and above, ' .
                'you will most likely encounter problems running it with PHP ' . PHP_VERSION .
                ', upgrading is strongly recommended.</warning>'
            );
        }

        if (defined('TENSIDE_DEV_WARNING_TIME')) {
            $commandName = '';
            if ($name = $this->getCommandName($input)) {
                try {
                    $commandName = $this->find($name)->getName();
                } catch (\InvalidArgumentException $e) {
                    // Swallow the exception.
                }
            }
            if ($commandName !== 'self-update' && $commandName !== 'selfupdate') {
                if (time() > TENSIDE_DEV_WARNING_TIME) {
                    $output->writeln(
                        sprintf(
                            '<warning>Warning: This development build of tenside is over 30 days old. ' .
                            'It is recommended to update it by running "%s self-update" to get the latest version.' .
                            '</warning>',
                            $_SERVER['PHP_SELF']
                        )
                    );
                }
            }
        }

        if (getenv('COMPOSER_NO_INTERACTION')) {
            $input->setInteractive(false);
        }

        if ($input->hasParameterOption('--profile')) {
            $startTime = microtime(true);
            $this->io->enableDebugging($startTime);
        }

        $result = parent::doRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        if (isset($startTime)) {
            $output->writeln(
                '<info>Memory usage: ' .
                round((memory_get_usage() / 1024 / 1024), 2) .
                'MB (peak: ' .
                round((memory_get_peak_usage() / 1024 / 1024), 2) .
                'MB), time: ' .
                round((microtime(true) - $startTime), 2) . 's</info>'
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        // FIXME: we should check if the command needs the composer instance.
        if ($command instanceof \Composer\Command\Command) {
            $command->setComposer($this->kernel->getContainer()->get('tenside')->getComposer());
        }

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * Register all commands from the container and bundles in the application.
     *
     * @param OutputInterface $output The output handler to use.
     *
     * @return void
     */
    protected function registerCommands(OutputInterface $output)
    {
        $container = $this->kernel->getContainer();

        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof Bundle) {
                $bundle->registerCommands($this);
            }
        }

        if ($container->hasParameter('console.command.ids')) {
            foreach ($container->getParameter('console.command.ids') as $id) {
                $this->add($container->get($id));
            }
        }

        /** @var ComposerJson $file */
        $file = $container->get('tenside.composer_json');

        // Add non-standard scripts as own commands - keep this last to ensure we do not override internal commands.
        if ($file->has('scripts')) {
            foreach (array_keys($file->get('scripts')) as $script) {
                if (!defined('Composer\Script\ScriptEvents::'.str_replace('-', '_', strtoupper($script)))) {
                    if ($this->has($script)) {
                        $output->writeln(
                            sprintf(
                                '<warning>' .
                                'A script named %s would override a native function and has been skipped' .
                                '</warning>',
                                $script
                            )
                        );
                        continue;
                    }
                    $this->add(new ScriptAliasCommand($script));
                }
            }
        }
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param Command $command A Command object
     *
     * @return Command The registered command
     *
     * @api
     */
    public function add(Command $command)
    {
        // We have to skip any command requiring the ability to manipulate the container and/or filesystem within the
        // phar file.
        if ('phar' === $this->kernel->getEnvironment()
            && in_array($command->getName(), ['debug:container', 'assets:install', 'cache:clear', 'cache:warmup'])) {
            return $command;
        }

        return parent::add($command);
    }

    /**
     * {@inheritDoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $styles    = ComposerFactory::createAdditionalStyles();
            $formatter = new OutputFormatter(null, $styles);
            $output    = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, null, $formatter);
        }

        return parent::run($input, $output);
    }

    /**
     * Return the help string.
     *
     * @return string
     */
    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    /**
     * {@inheritDoc}
     */
    public function getLongVersion()
    {
        if (Tenside::BRANCH_ALIAS_VERSION) {
            return sprintf(
                '<info>%s</info> version <comment>%s (%s)</comment> %s',
                $this->getName(),
                Tenside::BRANCH_ALIAS_VERSION,
                $this->getVersion(),
                Tenside::RELEASE_DATE
            );
        }

        return parent::getLongVersion() . ' ' . Tenside::RELEASE_DATE;
    }
}
