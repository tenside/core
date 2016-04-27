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

namespace Tenside\Core\Config;

/**
 * Main tenside configuration (abstraction over tenside.json).
 */
class TensideJsonConfig extends SourceJson
{
    /**
     * Create a new instance.
     *
     * @param string $directory The directory where the tenside.json shall be placed.
     */
    public function __construct($directory)
    {
        parent::__construct($directory . DIRECTORY_SEPARATOR . 'tenside.json');
    }

    /**
     * Retrieve the secret.
     *
     * @return string|null
     */
    public function getSecret()
    {
        return $this->getIfNotNull('secret', null);
    }

    /**
     * Retrieve the domain.
     *
     * @return string|null
     */
    public function getLocalDomain()
    {
        return $this->getIfNotNull('domain', null);
    }

    /**
     * Get the interpreter to use.
     *
     * @return string
     */
    public function getPhpCliBinary()
    {
        // If defined, override the php-cli interpreter.
        return $this->getIfNotNull('php_cli_arguments', 'php');
    }

    /**
     * Retrieve the arguments to pass to the php process
     *
     * @return string|null
     */
    public function getPhpCliArguments()
    {
        return $this->getIfNotNull('php_cli_arguments', null);
    }

    /**
     * Retrieve the additional environment variables.
     *
     * @return string|null
     */
    public function getPhpCliEnvironment()
    {
        return $this->getIfNotNull('php_cli_environment', null);
    }

    /**
     * Check if forking is available.
     *
     * @return string|null
     */
    public function isForkingAvailable()
    {
        return $this->getIfNotNull('php_can_fork', false);
    }

    /**
     * Obtain a value from if it is set or return the default value otherwise.
     *
     * @param string $key     The key to obtain.
     *
     * @param mixed  $default The default value to return if not set.
     *
     * @return mixed
     */
    private function getIfNotNull($key, $default = null)
    {
        return $this->has($key) ? $this->get($key) : $default;
    }
}
