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
     * Set the secret.
     *
     * @param string $secret The new secret.
     *
     * @return TensideJsonConfig
     */
    public function setSecret($secret)
    {
        $this->set('secret', $secret);

        return $this;
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
     * Set the domain.
     *
     * @param string $domain The new domain.
     *
     * @return TensideJsonConfig
     */
    public function setLocalDomain($domain)
    {
        $this->set('domain', $domain);

        return $this;
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
     * Set the interpreter to use.
     *
     * @param string $binary The new interpreter to use.
     *
     * @return TensideJsonConfig
     */
    public function setPhpCliBinary($binary)
    {
        $this->set('php_cli_arguments', $binary);

        return $this;
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
     * Set the arguments to use.
     *
     * @param array $arguments The new arguments to use.
     *
     * @return TensideJsonConfig
     */
    public function setPhpCliArguments($arguments)
    {
        $this->set('php_cli_arguments', $arguments);

        return $this;
    }

    /**
     * Retrieve the additional environment variables.
     *
     * @return array|null
     */
    public function getPhpCliEnvironment()
    {
        return $this->getIfNotNull('php_cli_environment', null);
    }

    /**
     * Set the additional environment variables.
     *
     * @param array $variables The new arguments to use.
     *
     * @return TensideJsonConfig
     */
    public function setPhpCliEnvironment($variables)
    {
        $this->set('php_cli_environment', $variables);

        return $this;
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
     * Set the additional environment variables.
     *
     * @param bool $available The new arguments to use.
     *
     * @return TensideJsonConfig
     */
    public function setForkingAvailable($available)
    {
        $this->set('php_can_fork', $available);

        return $this;
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
