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
