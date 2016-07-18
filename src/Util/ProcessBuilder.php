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

namespace Tenside\Core\Util;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * This class is a process builder used in tenside.
 *
 * It is heavily based upon the ProcessBuilder by symfony but more granular and adds missing features.
 */
class ProcessBuilder
{
    /**
     * The CLI executable to launch.
     *
     * @var string
     */
    private $binary;

    /**
     * The CLI arguments to pass.
     *
     * @var string[]
     */
    private $arguments = [];

    /**
     * The working directory.
     *
     * @var string
     */
    private $workingDirectory;

    /**
     * The environment variables.
     *
     * @var array
     */
    private $environment = [];

    /**
     * Flag determining if the current environment shall get inherited.
     *
     * @var bool
     */
    private $inheritEnvironment = true;

    /**
     * The input content.
     *
     * @var resource|scalar|\Traversable|null
     */
    private $input;

    /**
     * The timeout value.
     *
     * @var int|null
     */
    private $timeout = 60;

    /**
     * The proc_open options to use.
     *
     * @var array
     */
    private $options = [];

    /**
     * Flag determining if the output shall be disabled.
     *
     * @var bool
     */
    private $outputDisabled = false;

    /**
     * Flag determining if task shall get spawned into background.
     *
     * @var bool
     */
    private $forceBackground = false;

    /**
     * Create a new instance.
     *
     * @param string   $binary    The binary to launch.
     *
     * @param string[] $arguments An array of arguments.
     *
     * @return ProcessBuilder
     */
    public static function create($binary, array $arguments = [])
    {
        return new static($binary, $arguments);
    }

    /**
     * Constructor.
     *
     * @param string   $binary    The binary to launch.
     *
     * @param string[] $arguments An array of arguments.
     */
    public function __construct($binary, array $arguments = [])
    {
        $this->binary = $binary;
        $this->setArguments($arguments);
    }

    /**
     * Adds an unescaped argument to the command string.
     *
     * @param string $argument A command argument.
     *
     * @return ProcessBuilder
     */
    public function addArgument($argument)
    {
        $this->arguments[] = (string) $argument;

        return $this;
    }

    /**
     * Adds unescaped arguments to the command string.
     *
     * @param string[] $arguments The command arguments.
     *
     * @return ProcessBuilder
     */
    public function addArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }

        return $this;
    }

    /**
     * Sets the arguments of the process.
     *
     * Arguments must not be escaped.
     * Previous arguments are removed.
     *
     * @param string[] $arguments The new arguments.
     *
     * @return ProcessBuilder
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = [];
        if ([] !== $arguments) {
            $this->addArguments($arguments);
        }

        return $this;
    }

    /**
     * Sets the working directory.
     *
     * @param null|string $workingDirectory The working directory.
     *
     * @return ProcessBuilder
     *
     * @throws InvalidArgumentException When the working directory is non null and does not exist.
     */
    public function setWorkingDirectory($workingDirectory)
    {
        if ($workingDirectory && !is_dir($workingDirectory)) {
            throw new InvalidArgumentException('The working directory must exist.');
        }

        $this->workingDirectory = $workingDirectory;

        return $this;
    }

    /**
     * Sets whether environment variables will be inherited or not.
     *
     * @param bool $inheritEnvironment Flag if the environment shall get inherited or not (default true).
     *
     * @return ProcessBuilder
     */
    public function inheritEnvironmentVariables($inheritEnvironment = true)
    {
        $this->inheritEnvironment = $inheritEnvironment;

        return $this;
    }

    /**
     * Sets an environment variable.
     *
     * Setting a variable overrides its previous value. Use `null` to unset a
     * defined environment variable.
     *
     * @param string      $name  The variable name.
     *
     * @param null|string $value The variable value.
     *
     * @return ProcessBuilder
     */
    public function setEnv($name, $value)
    {
        $this->environment[$name] = $value;

        return $this;
    }

    /**
     * Adds a set of environment variables.
     *
     * Already existing environment variables with the same name will be
     * overridden by the new values passed to this method. Pass `null` to unset
     * a variable.
     *
     * @param array $variables The variables.
     *
     * @return ProcessBuilder
     */
    public function addEnvironmentVariables(array $variables)
    {
        $this->environment = array_replace($this->environment, $variables);

        return $this;
    }

    /**
     * Sets the input of the process.
     *
     * @param resource|scalar|\Traversable|null $input The input content.
     *
     * @return ProcessBuilder
     *
     * @throws InvalidArgumentException In case the argument is invalid.
     */
    public function setInput($input)
    {
        $this->input = ProcessUtils::validateInput(__METHOD__, $input);

        return $this;
    }

    /**
     * Sets the process timeout.
     *
     * To disable the timeout, set this value to null.
     *
     * @param float|null $timeout The new timeout value.
     *
     * @return ProcessBuilder
     *
     * @throws InvalidArgumentException When the timeout value is negative.
     */
    public function setTimeout($timeout)
    {
        if (null === $timeout) {
            $this->timeout = null;

            return $this;
        }

        $timeout = (float) $timeout;

        if ($timeout < 0) {
            throw new InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Adds a proc_open() option.
     *
     * @param string $name  The option name.
     *
     * @param string $value The option value.
     *
     * @return ProcessBuilder
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Disables fetching output and error output from the underlying process.
     *
     * @return ProcessBuilder
     */
    public function disableOutput()
    {
        $this->outputDisabled = true;

        return $this;
    }

    /**
     * Enables fetching output and error output from the underlying process.
     *
     * @return ProcessBuilder
     */
    public function enableOutput()
    {
        $this->outputDisabled = false;

        return $this;
    }

    /**
     * Set if the process execution should be forced into the background.
     *
     * @param boolean $forceBackground The new value.
     *
     * @return ProcessBuilder
     */
    public function setForceBackground($forceBackground = true)
    {
        $this->forceBackground = $forceBackground;

        return $this;
    }

    /**
     * Generate the process.
     *
     * @return Process
     */
    public function generate()
    {
        $options = $this->options;

        $arguments = array_merge([$this->binary], (array) $this->arguments);
        $script    = implode(' ', array_map([ProcessUtils::class, 'escapeArgument'], $arguments));
        $process   = new Process(
            $this->applyForceToBackground($script),
            $this->workingDirectory,
            $this->getEnvironmentVariables(),
            $this->input,
            $this->timeout,
            $options
        );

        if ($this->outputDisabled) {
            $process->disableOutput();
        }

        return $process;
    }

    /**
     * Retrieve the passed environment variables from the current session and return them.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function getEnvironmentVariables()
    {
        // Initialize from globals, allow override of special keys via putenv calls.
        $variables = $this->inheritEnvironment
            ? array_replace($_ENV, $_SERVER, $this->environment)
            : $this->environment;

        foreach (array_keys($variables) as $name) {
            if (false !== ($value = getenv($name))) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }

    /**
     * Apply the force to background flag to a command line.
     *
     * @param string $commandLine The current command line to execute.
     *
     * @return string
     */
    private function applyForceToBackground($commandLine)
    {
        if ($this->forceBackground) {
            if ('\\' === DIRECTORY_SEPARATOR) {
                return 'start /B ' . $commandLine;
            }
            return $commandLine . ' &';
        }

        return $commandLine;
    }
}
