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
 * This class provides information about the current installation.
 */
class InstallationStatusDeterminator
{
    /**
     * The home path determinator.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * Local cache flag determining if tenside is properly configured.
     *
     * @var null|bool
     */
    private $isTensideConfigured;

    /**
     * Local cache flag determining if a composer.json is present.
     *
     * @var null|bool
     */
    private $isProjectPresent;

    /**
     * Local cache flag determining if the composer project has been installed (vendor present).
     *
     * @var null|bool
     */
    private $isProjectInstalled;

    /**
     * Create a new instance.
     *
     * @param HomePathDeterminator $homePathDeterminator The home path determinator.
     */
    public function __construct(HomePathDeterminator $homePathDeterminator)
    {
        $this->home = $homePathDeterminator;
    }

    /**
     * Check if a "tenside.json" is present.
     *
     * @return bool
     */
    public function isTensideConfigured()
    {
        if (isset($this->isTensideConfigured)) {
            return $this->isTensideConfigured;
        }

        return $this->isTensideConfigured = file_exists(
            $this->home->tensideDataDir() . DIRECTORY_SEPARATOR . 'tenside.json'
        );
    }

    /**
     * Check if a project "composer.json" is present.
     *
     * @return bool
     */
    public function isProjectPresent()
    {
        if (isset($this->isProjectPresent)) {
            return $this->isProjectPresent;
        }

        return $this->isProjectPresent = file_exists($this->home->homeDir() . DIRECTORY_SEPARATOR . 'composer.json');
    }

    /**
     * Check if the vendor directory is present.
     *
     * @return bool
     */
    public function isProjectInstalled()
    {
        if (isset($this->isProjectInstalled)) {
            return $this->isProjectInstalled;
        }

        return $this->isProjectInstalled = is_dir($this->home->homeDir() . DIRECTORY_SEPARATOR . 'vendor');
    }

    /**
     * Flag if everything is completely installed.
     *
     * @return bool
     */
    public function isComplete()
    {
        return $this->isTensideConfigured()
            && $this->isProjectPresent()
            && $this->isProjectInstalled();
    }
}
