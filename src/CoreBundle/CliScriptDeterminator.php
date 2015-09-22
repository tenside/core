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

namespace Tenside\CoreBundle;

/**
 * This class provides information about the cli command to use throughout tenside.
 */
class CliScriptDeterminator
{
    /**
     * The executable to use in non-phar mode.
     *
     * @var string
     */
    private $scriptName;

    /**
     * The cached value.
     *
     * @var string
     */
    private $cachedValue;

    /**
     * Create a new instance.
     *
     * @param string $scriptName The executable to use in non-phar mode.
     */
    public function __construct($scriptName)
    {
        $this->scriptName = $scriptName;
    }

    /**
     * Retrieve the home directory.
     *
     * @return string
     */
    public function cliExecutable()
    {
        if (null !== $this->cachedValue) {
            return $this->cachedValue;
        }

        return $this->cachedValue = $this->detectCliExecutable();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->cliExecutable();
    }

    /**
     * Determine the correct executable.
     *
     * @return string
     */
    private function detectCliExecutable()
    {
        return \Phar::running(false) ?: $this->scriptName;
    }
}
