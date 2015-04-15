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

use Tenside\Config\SourceInterface;
use Tenside\Web\UserInformation;

/**
 * The main tenside instance.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AuthFromConfig implements AuthUserPasswordInterface
{
    /**
     * The config source to use.
     *
     * @var SourceInterface
     */
    private $configSource;

    /**
     * Create a new instance.
     *
     * @param SourceInterface $config The config source to read the user data from.
     */
    public function __construct(SourceInterface $config)
    {
        $this->configSource = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function addUser($username, $password, $role)
    {
        $this->configSource->set(
            $username,
            [
                'password' => $this->encrypt($password, uniqid('', true)),
                'role' => $role
            ]
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeUser($username)
    {
        $this->configSource->set($username, null);
    }

    /**
     * Validate the passed credentials.
     *
     * @param string $username The username to check.
     *
     * @param string $password The password to check.
     *
     * @return string|null
     */
    public function validate($username, $password)
    {
        if ($this->configSource->has($username)) {
            $userData = $this->configSource->get($username);
            $salt     = explode(':', $userData['password'], 2)[1];
            if ($userData['password'] === $this->encrypt($password, $salt)) {
                return UserInformation::createFromArray(
                    [
                        'id' => $username,
                        'user' => $username,
                        'password' => $password,
                        'role' => $userData['role']
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Encrypt the passed password with the salt.
     *
     * @param string $password The password to encrypt.
     *
     * @param string $salt     The salt to use.
     *
     * @return string
     */
    private function encrypt($password, $salt)
    {
        // FIXME: make this more difficult.
        return sha1($password . $salt) . ':' . $salt;
    }
}
