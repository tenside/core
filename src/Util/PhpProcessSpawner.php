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

use Symfony\Component\Process\Process;
use Tenside\Core\Config\TensideJsonConfig;

/**
 * This class implements a php process spawner.
 */
class PhpProcessSpawner
{
    /**
     * The configuration in use.
     *
     * @var TensideJsonConfig
     */
    private $config;

    /**
     * The home directory to run the process in.
     *
     * @var string
     */
    private $homePath;

    /**
     * Create a new instance.
     *
     * @param TensideJsonConfig $config   The configuration in use.
     *
     * @param string            $homePath The directory to use as home directory.
     */
    public function __construct(TensideJsonConfig $config, $homePath)
    {
        $this->config   = $config;
        $this->homePath = $homePath;
    }

    /**
     * Create a new instance.
     *
     * @param TensideJsonConfig $config   The configuration in use.
     *
     * @param string            $homePath The directory to use as home directory.
     *
     * @return PhpProcessSpawner
     */
    public static function create(TensideJsonConfig $config, $homePath)
    {
        return new static($config, $homePath);
    }

    /**
     * Run the process.
     *
     * @param array $arguments The additional arguments to add to the call.
     *
     * @return Process
     */
    public function spawn($arguments)
    {
        $cmd = sprintf(
            '%s %s',
            escapeshellcmd($this->config->getPhpCliBinary()),
            $this->getArguments($arguments)
        );

        if ($this->config->isForceToBackgroundEnabled()) {
            $cmd .= '&';
        }

        return new Process($cmd, $this->homePath, $this->getEnvironment(), null, null);
    }

    /**
     * Retrieve the command line arguments to use.
     *
     * @param array $additionalArguments The additional arguments to add to the call.
     *
     * @return string
     */
    private function getArguments($additionalArguments)
    {
        $arguments = [];
        if (null !== ($cliArguments = $this->config->getPhpCliArguments())) {
            foreach ($cliArguments as $argument) {
                $arguments[] = $argument;
            }
        }
        $arguments = array_map('escapeshellarg', array_merge($arguments, $additionalArguments));

        return implode(' ', $arguments);
    }

    /**
     * Retrieve the command line environment variables to use.
     *
     * @return array
     */
    private function getEnvironment()
    {
        $variables = $this->getDefinedEnvironmentVariables();

        if (null === ($environment = $this->config->getPhpCliEnvironment())) {
            return $variables;
        }

        $variables = array_merge($variables, $environment);

        return $variables;
    }

    /**
     * Retrieve the passed environment variables from the current session and return them.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function getDefinedEnvironmentVariables()
    {
        $names = array_merge(
            ['SYMFONY_ENV', 'SYMFONY_DEBUG', 'COMPOSER', 'HOME', 'USER', 'PATH'],
            array_keys($_ENV)
        );

        $variables = [];
        foreach ($names as $name) {
            if (false !== ($composerEnv = getenv($name))) {
                $variables[$name] = $composerEnv;
            }
        }

        return $variables;
    }
}
