<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
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

        // FIXME: check that this really works correctly in CLI mode.
        if (false === getenv('COMPOSER')) {
            // FIXME: really only one up? What about aliased web root in apache?
            putenv('COMPOSER=' . dirname($home));
            chdir(dirname($home));
        }
    }
}