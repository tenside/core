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

namespace Tenside\CoreBundle\DependencyInjection\Factory;

use Tenside\CoreBundle\HomePathDeterminator;
use Tenside\CoreBundle\TensideJsonConfig;

/**
 * This class creates a config instance.
 */
class TensideJsonConfigFactory
{
    /**
     * Create an instance.
     *
     * @param HomePathDeterminator $home The home determinator.
     *
     * @return TensideJsonConfig
     */
    public static function create(HomePathDeterminator $home)
    {
        return new TensideJsonConfig($home->homeDir());
    }
}
