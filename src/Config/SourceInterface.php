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
 * Abstract base class for configs.
 */
interface SourceInterface
{
    /**
     * Retrieve a config key.
     *
     * @param string $path       The path to the value to be retrieved.
     *
     * @param bool   $forceArray Flag if the result shall be forced to be of array nature.
     *
     * @return mixed
     */
    public function get($path, $forceArray = false);

    /**
     * Check if a value exists.
     *
     * @param string $path The path to the value to be checked.
     *
     * @return bool
     */
    public function has($path);

    /**
     * Set a value.
     *
     * @param string $path  The path to the value value to be set.
     *
     * @param mixed  $value The value to be stored.
     *
     * @return SourceInterface
     */
    public function set($path, $value);
}
