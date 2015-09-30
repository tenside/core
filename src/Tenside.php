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

namespace Tenside;

use Composer\Composer;
use Composer\Factory as ComposerFactory;
use Composer\IO\BufferIO;
use Tenside\CoreBundle\HomePathDeterminator;
use Tenside\Util\RuntimeHelper;

/**
 * The main tenside instance.
 */
class Tenside
{
    /**
     * The version.
     */
    const VERSION = '@package_version@';

    /**
     * The branch alias from the composer.json.
     */
    const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    /**
     * The release date.
     */
    const RELEASE_DATE = '@release_date@';

    /**
     * The home directory.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * The composer instance.
     *
     * @var Composer
     */
    private $composer;

    /**
     * Create a new instance.
     *
     * @param HomePathDeterminator $home The home path determinator.
     */
    public function __construct(HomePathDeterminator $home)
    {
        $this->home = $home;
    }

    /**
     * Load composer.
     *
     * @return Composer
     *
     * @deprecated Create the composer instance where needed with correct i/o instance.
     */
    public function getComposer()
    {
        if (!isset($this->composer)) {
            RuntimeHelper::setupHome($this->home->homeDir());

            $this->composer = ComposerFactory::create(new BufferIO());
        }

        return $this->composer;
    }

    /**
     * Check if the installation is already done.
     *
     * @return bool
     */
    public function isInstalled()
    {
        $homeDir = $this->home->homeDir();
        return (file_exists($homeDir . DIRECTORY_SEPARATOR . 'composer.json')
            && file_exists($homeDir . DIRECTORY_SEPARATOR . 'tenside.json'));
    }
}
