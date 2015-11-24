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
}
