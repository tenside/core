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
 * This interface describes a user information.
 */
interface UserInformationInterface
{
    /**
     * The key used for storing the access level,
     */
    const KEY_ACL = 'acl';

    /**
     * Perform package upgrades.
     */
    const ACL_UPGRADE = 1;

    /**
     * Manipulate the package requirements.
     */
    const ACL_MANIPULATE_REQUIREMENTS = 2;

    /**
     * Edit the composer.json.
     */
    const ACL_EDIT_COMPOSER_JSON = 4;

    /**
     * All access (aka admin).
     */
    const ACL_ALL = 7;

    /**
     * Check if the user has the given access level.
     *
     * @param string $accessLevel The access level to check.
     *
     * @return bool
     */
    public function hasAccessLevel($accessLevel);

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     * @api
     */
    public function keys();

    /**
     * Returns the parameter values as associative array.
     *
     * @return array
     */
    public function values();

    /**
     * Returns a value by name.
     *
     * @param string $key     The value name.
     *
     * @param mixed  $default The default value.
     *
     * @return string|null The value if found, null otherwise.
     *
     * @api
     */
    public function get($key, $default = null);

    /**
     * Returns true if the HTTP header is defined.
     *
     * @param string $key The key.
     *
     * @return bool true if the value exists, false otherwise.
     *
     * @api
     */
    public function has($key);


    /**
     * String representation of this user information for use in logs.
     *
     * Examples may be: "user foo" or "token 0123456789".
     *
     * @return mixed
     */
    public function asString();
}
