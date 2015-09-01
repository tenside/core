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

namespace Tenside\Web\Auth;

/**
 * This interface describes token validation services.
 */
interface TokenValidatorInterface extends AuthInterface
{
    /**
     * Create a token from the passed user information.
     *
     * @param UserInformationInterface $userData     The user data to issue a token for.
     *
     * @param null|int                 $invalidAfter Optional timestamp after when the token shall be invalid.
     *
     * @return string
     */
    public function getTokenForData($userData, $invalidAfter = null);
}
