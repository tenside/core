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
 * Authentication provider that reads the data from a config file.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AuthorizationFromConfig extends AbstractAuthorizationValidator implements AuthUserPasswordInterface
{
    /**
     * {@inheritdoc}
     */
    public function getChallenge()
    {
        // Note: we explicitely set the type to Tenside here instead of Basic as otherwise browsers will show popups.
        return 'TensideBasic realm="user+password"';
    }

    /**
     * Add the passed credentials in the database.
     *
     * @param string $username    The username to add.
     *
     * @param string $password    The password to set for the user.
     *
     * @param int    $accessLevel The granted access levels.
     *
     * @return AuthorizationFromConfig
     */
    public function addUser($username, $password, $accessLevel)
    {
        $this->configSource->set(
            'auth-password/' . $username,
            [
                'password' => $this->encrypt($password, uniqid('', true)),
                'acl' => $accessLevel
            ]
        );

        return $this;
    }

    /**
     * Add the passed credentials in the database.
     *
     * @param string $username The username to remove.
     *
     * @return AuthorizationFromConfig
     */
    public function removeUser($username)
    {
        $this->configSource->set('auth-password/' . $username, null);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsScheme($scheme, $data)
    {
        if (('basic' !== strtolower($scheme)) && ('tensidebasic' !== strtolower($scheme))) {
            return false;
        }

        $decoded = base64_decode($data);

        if ((false === $decoded) || false === strpos($decoded, ':')) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function authenticateScheme($scheme, $data)
    {
        list($username, $password) = explode(':', base64_decode($data));

        return $this->validate($username, $password);
    }

    /**
     * Validate the passed credentials.
     *
     * @param string $username The username to check.
     *
     * @param string $password The password to check.
     *
     * @return UserInformationInterface|null
     */
    private function validate($username, $password)
    {
        if ($this->configSource->has('auth-password/' . $username)) {
            $userData = $this->configSource->get('auth-password/' . $username);
            $salt     = explode(':', $userData['password'], 2)[1];
            if ($userData['password'] === $this->encrypt($password, $salt)) {
                return new UserInformation(
                    [
                        'id' => $username,
                        'user' => $username,
                        'acl' => $userData['acl']
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
