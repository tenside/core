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

namespace Tenside\Util;

/**
 * Generic helper method collection for being able to provide an web entry point and an cli entry point.
 */
class RuntimeHelper
{
    /**
     * Detect the correct tenside home dir and set the environment variable.
     *
     * @param string $home The home directory.
     *
     * @return void
     *
     * @throws \InvalidArgumentException For empty value of $home.
     */
    public static function setupHome($home)
    {
        if (empty($home)) {
            throw new \InvalidArgumentException('Empty home directory encountered.');
        }

        // FIXME: check that this really works correctly in CLI mode.
        if (false === getenv('COMPOSER')) {
            putenv('COMPOSER=' . $home . '/composer.json');
        }
        chdir($home);

        // Ensure at least one of the environment variables is available.
        if (!getenv('COMPOSER_HOME') && !getenv('HOME')) {
            putenv('COMPOSER_HOME=' . $home);
        }
    }
}
