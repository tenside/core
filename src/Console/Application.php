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
use Symfony\Component\Console\Application as SymfonyApplication;
use Composer\Console\Application as BaseApplication;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Composer\Factory as ComposerFactory;
use Tenside\Console\Command\RunTaskCommand;
use Tenside\Factory;
use Tenside\Tenside;
use Tenside\Util\RuntimeHelper;

/**
 * The console application that handles the commands.
 */
class Application extends BaseApplication
{
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
     * The tenside instance.
     *
     * @var Tenside
     */
    private $tenside;

    /**
     * Create the instance.
     */
    public function __construct()
    {
        if (function_exists('ini_set') && extension_loaded('xdebug')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(date_default_timezone_get());
        }

        ErrorHandler::register();

        // Hop over the composer constructor - do NOT call parent::__construct().
        SymfonyApplication::__construct('Tenside', Tenside::VERSION);

        RuntimeHelper::setupHome();
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
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

        // Switch working dir.
        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
            if ($output->getVerbosity() >= 4) {
                $output->writeln('Changed CWD to ' . getcwd());
            }
        }

        // FIXME: Add a cycle here to check installed.json for tenside-plugins and boot them here.

        // Add non-standard scripts as own commands.
        $file = ComposerFactory::getComposerFile();
        if (is_file($file) && is_readable($file) && is_array($composer = json_decode(file_get_contents($file), true))) {
            if (isset($composer['scripts']) && is_array($composer['scripts'])) {
                foreach (array_keys($composer['scripts']) as $script) {
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
                        } else {
                            $this->add(new ScriptAliasCommand($script));
                        }
                    }
                }
            }
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
     * Determine the working directory passed via command line, check and return it.
     *
     * @param InputInterface $input The input output interface.
     *
     * @return string
     *
     * @throws \RuntimeException When the passed working directory is invalid.
     */
    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(array('--working-dir', '-d'));
        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
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
     * Initializes all the composer commands.
     *
     * @return Command[]
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        // FIXME: we MUST check which commands we can provide and which not.
        $newCommands = array();
        foreach ($commands as $command) {
            // Self update would download composer instead of tenside, kill it.
            if ($command instanceof \Composer\Command\SelfUpdateCommand) {
                continue;
            }
            $newCommands[] = $command;
        }
        $newCommands[] = new RunTaskCommand();

        return $newCommands;
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
     * Retrieve the tenside instance.
     *
     * @return Tenside
     */
    public function getTenside()
    {
        if (!$this->tenside) {
            $this->tenside = Factory::create();
        }

        return $this->tenside;
    }
}
