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

namespace Tenside\Web;

/**
 * A user information..
 */
class UserInformation
{
    /**
     * Generic role - all users have this.
     */
    const ROLE_ALL = '*';

    /**
     * Admin role.
     */
    const ROLE_ADMIN = 'admin';

    /**
     * Editor role.
     */
    const ROLE_EDITOR = 'editor';

    /**
     * Guest role.
     */
    const ROLE_GUEST = 'guest';

    /**
     * The user id.
     *
     * @var string
     */
    private $userId;

    /**
     * The user name.
     *
     * @var string
     */
    private $userName;

    /**
     * The password hash.
     *
     * @var string
     */
    private $passwordHash;

    /**
     * The user role.
     *
     * @var string
     */
    private $userRole;

    /**
     * Get the user id.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the user id.
     *
     * @param string $userId The user id.
     *
     * @return UserInformation
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the user name.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set the user name.
     *
     * @param string $userName The user name.
     *
     * @return UserInformation
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get the password hash.
     *
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    /**
     * Set the password hash.
     *
     * @param string $passwordHash The password hash.
     *
     * @return UserInformation
     */
    public function setPasswordHash($passwordHash)
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    /**
     * Get the user role.
     *
     * @return string
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * Set the user role.
     *
     * @param string $userRole The user role.
     *
     * @return UserInformation
     */
    public function setUserRole($userRole)
    {
        $this->userRole = $userRole;

        return $this;
    }

    /**
     * Check if the user has the given role..
     *
     * @param string $userRole The user role.
     *
     * @return UserInformation
     */
    public function hasRole($userRole)
    {
        return (self::ROLE_ALL === $userRole) || ($this->userRole === $userRole);
    }

    /**
     * Retrieve the information as array.
     *
     * This will exclude the password field unless $includeAll is given.
     *
     * @param bool $includeAll If given, private data like the password will also be exported.
     *
     * @return array
     */
    public function asArray($includeAll = false)
    {
        $array = array(
            'id'   => $this->getUserId(),
            'user' => $this->getUserName(),
            'role' => $this->getUserRole()
        );

        if ($includeAll) {
            $array['password'] = $this->getPasswordHash();
        }

        return $array;
    }

    /**
     * Create an instance from array.
     *
     * @param array $array The array.
     *
     * @return static
     */
    public static function createFromArray($array)
    {
        $instance           = new static();
        $instance->userId   = $array['id'];
        $instance->userName = $array['user'];
        $instance->userRole = $array['role'];

        if (isset($array['password'])) {
            $instance->passwordHash = $array['password'];
        }

        return $instance;
    }
}
