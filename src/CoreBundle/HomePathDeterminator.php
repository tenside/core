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
 * This class provides information about the home path to use throughout tenside.
 */
class HomePathDeterminator
{
    /**
     * The cached value.
     *
     * @var string
     */
    private $home;

    /**
     * Retrieve the home directory.
     *
     * @return string
     */
    public function homeDir()
    {
        if (null !== $this->home) {
            return $this->home;
        }

        return $this->home = $this->detectHomeDirectory();
    }

    /**
     * Determine the correct working directory.
     *
     * @return string
     *
     * @throws \RuntimeException When the home directory is not /web.
     */
    private function detectHomeDirectory()
    {
        // Environment variable COMPOSER points to the composer.json we should use. The tenside.json is alongside.
        if (false !== ($home = getenv('COMPOSER'))) {
            return dirname($home);
        }

        if ('' !== \Phar::running()) {
            // Strip scheme "phar://" prefix and "tenside.phar" suffix.
            $home = dirname(substr(\Phar::running(), 7));
        } else {
            $home = getcwd();
        }

        if ((PHP_SAPI !== 'cli') && (substr($home, -4) !== '/web')) {
            throw new \RuntimeException(
                'Tenside is intended to be run from within the web directory but it appears you are running it from ' .
                basename($home)
            );
        }

        return dirname($home);
    }
}
