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

use Tenside\Web\UserInformation;

/**
 * This interface is an empty base for auth providers.
 */
interface AuthUserPasswordInterface extends AuthInterface
{
    /**
     * Add the passed credentials in the database.
     *
     * @param string $username The username to add.
     *
     * @param string $password The password to set for the user.
     *
     * @param string $role     Any of the roles.
     *
     * @return AuthUserPasswordInterface
     */
    public function addUser($username, $password, $role);

    /**
     * Add the passed credentials in the database.
     *
     * @param string $username The username to remove.
     *
     * @return AuthUserPasswordInterface
     */
    public function removeUser($username);

    /**
     * Validate the passed credentials.
     *
     * @param string $username The username to check.
     *
     * @param string $password The password to check.
     *
     * @return UserInformation|null
     */
    public function validate($username, $password);
}
