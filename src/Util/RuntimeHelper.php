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
     * @return void
     *
     * @throws \RuntimeException When the home directory is not /web.
     */
    public static function setupHome()
    {
        if ('' !== \Phar::running()) {
            // Strip scheme "phar://" prefix and "tenside.phar" suffix.
            $home = dirname(substr(\Phar::running(), 7));
        } else {
            if (false === ($home = getenv('TENSIDE_HOME'))) {
                $home = getcwd();
            };
        }

        if ((PHP_SAPI !== 'cli') && (substr($home, -4) !== '/web')) {
            throw new \RuntimeException(
                'Tenside is intended to be run from within the web directory but it appears you are running it from ' .
                basename($home)
            );
        }
        // FIXME: really only one up? What about aliased web root in apache?
        $home = dirname($home);

        // FIXME: check that this really works correctly in CLI mode.
        if (false === getenv('COMPOSER')) {
            putenv('COMPOSER=' . $home . '/composer.json');
            chdir($home);
        }

        // Ensure at least one of the environment variables is available.
        if (!getenv('COMPOSER_HOME') && !getenv('HOME')) {
            putenv('COMPOSER_HOME=' . $home);
        }
    }
}
