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

use Composer\Command as ComposerCommand;
use Composer\Command\ScriptAliasCommand;
use Composer\IO\IOInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Shell;
use Symfony\Component\Console\Application as SymfonyApplication;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
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
     * The io interface in use.
     *
     * @var IOInterface
     */
    private $inputOutput;

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

        $this->ensurePath();

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(date_default_timezone_get());
        }

        $this->kernel = $kernel;

        parent::__construct(
            'Tenside',
            sprintf(
                '%s using symfony %s - %s/%s',
                Tenside::VERSION,
                Kernel::VERSION,
                $kernel->getName(),
                $kernel->getEnvironment(),
                ($kernel->isDebug() ? '/debug' : '')
            )
        );

        $definition = $this->getDefinition();
        $definition->addOption(new InputOption('--shell', null, InputOption::VALUE_NONE, 'Launch the shell.'));
        $definition->addOption(
            new InputOption(
                '--process-isolation',
                null,
                InputOption::VALUE_NONE,
                'Launch commands from shell as a separate process.'
            )
        );

        if (!\Phar::running()) {
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

        if (true === $input->hasParameterOption('--shell')) {
            $shell = new Shell($this);
            $shell->setProcessIsolation($input->hasParameterOption(['--process-isolation']));
            $shell->run();

            return 0;
        }

        RuntimeHelper::setupHome($container->get('tenside.home')->homeDir());

        $this->inputOutput = new ConsoleIO($input, $output, $this->getHelperSet());

        if (version_compare(PHP_VERSION, '5.4', '<')) {
            $output->writeln(
                '<warning>Tenside only officially supports PHP 5.4 and above, ' .
                'you will most likely encounter problems running it with PHP ' . PHP_VERSION .
                ', upgrading is strongly recommended.</warning>'
            );
        }

        $this->isUpdateNeeded($input, $output);

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        // FIXME: we should check if the command needs the composer instance.
        if ($command instanceof \Composer\Command\Command) {
            $command->setComposer(ComposerFactory::create($this->inputOutput));
            $command->setIO(new ConsoleIO($input, $output, $this->getHelperSet()));
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

        $this->addComposerCommands();

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
     * Add the composer base commands.
     *
     * @return void
     */
    protected function addComposerCommands()
    {
        $this->add(new ComposerCommand\AboutCommand());
        $this->add(new ComposerCommand\ConfigCommand());
        $this->add(new ComposerCommand\DependsCommand());
        $this->add(new ComposerCommand\InitCommand());
        $this->add(new ComposerCommand\InstallCommand());
        $this->add(new ComposerCommand\CreateProjectCommand());
        $this->add(new ComposerCommand\UpdateCommand());
        $this->add(new ComposerCommand\SearchCommand());
        $this->add(new ComposerCommand\ValidateCommand());
        $this->add(new ComposerCommand\ShowCommand());
        $this->add(new ComposerCommand\SuggestsCommand());
        $this->add(new ComposerCommand\RequireCommand());
        $this->add(new ComposerCommand\DumpAutoloadCommand());
        $this->add(new ComposerCommand\StatusCommand());
        $this->add(new ComposerCommand\ArchiveCommand());
        $this->add(new ComposerCommand\DiagnoseCommand());
        $this->add(new ComposerCommand\RunScriptCommand());
        $this->add(new ComposerCommand\LicensesCommand());
        $this->add(new ComposerCommand\GlobalCommand());
        $this->add(new ComposerCommand\ClearCacheCommand());
        $this->add(new ComposerCommand\RemoveCommand());
        $this->add(new ComposerCommand\HomeCommand());
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
     * {@inheritDoc}
     */
    public function renderException($exception, $output)
    {
        // Preserve plain echoing to console...
        parent::renderException($exception, $output);

        // ... but pass to logger as well.
        if ($container = $this->kernel->getContainer()) {
            /** @var LoggerInterface $logger */
            $logger = $container->get('logger');

            // We want stack traces, therefore be very verbose.
            $buffer = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
            parent::renderException($exception, $buffer);
            $logger->error('--------------------------------------------------------');
            foreach (explode("\n", str_replace("\n\n", "\n", $buffer->fetch())) as $line) {
                if ('' !== $line) {
                    $logger->error($line);
                }
            }
            $logger->error('--------------------------------------------------------');
        }
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

    /**
     * Check if updating is needed.
     *
     * @param InputInterface  $input  The input interface.
     * @param OutputInterface $output The output interface.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function isUpdateNeeded(InputInterface $input, OutputInterface $output)
    {
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

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ensure we have a PATH environment variable.
     *
     * @return void
     */
    protected function ensurePath()
    {
        // "git" binary not found when no PATH environment is present.
        // https://github.com/contao-community-alliance/composer-client/issues/54
        if (!getenv('PATH')) {
            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                putenv('PATH=%SystemRoot%\system32;%SystemRoot%;%SystemRoot%\System32\Wbem');
            } else {
                putenv('PATH=/opt/local/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin');
            }
        }
    }
}
