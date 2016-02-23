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

namespace Tenside\Core\Security;

/**
 * A user information..
 */
class UserInformation implements UserInformationInterface
{
    /**
     * The contained data.
     *
     * @var array
     */
    private $data = [];

    /**
     * The password salt.
     *
     * @var string
     */
    private $salt;

    /**
     * Map for translating roles to array of strings and vice versa.
     *
     * @var string[]
     */
    public static $roleMap = [
        UserInformationInterface::ROLE_UPGRADE                 => 'ROLE_UPGRADE',
        UserInformationInterface::ROLE_MANIPULATE_REQUIREMENTS => 'ROLE_MANIPULATE_REQUIREMENTS',
        UserInformationInterface::ROLE_EDIT_COMPOSER_JSON      => 'ROLE_EDIT_COMPOSER_JSON',
        UserInformationInterface::ROLE_EDIT_APPKERNEL          => 'ROLE_EDIT_APP_KERNEL',
    ];

    /**
     * Constructor.
     *
     * @param array $data An array of values.
     *
     * @api
     */
    public function __construct(array $data = [])
    {
        $this->salt = uniqid('', true);
        $this->setAccessLevel(0);
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getRoles()
    {
        $user       = $this;
        $comparator = function ($level) use ($user) {
            return $user->hasAccessLevel($level);
        };

        // Fallback for PHP < 5.6 which do not support ARRAY_FILTER_USE_KEY.
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $filtered = array_flip(array_filter(array_keys(self::$roleMap), $comparator));
            return array_values(array_intersect_key(self::$roleMap, $filtered));
        }

        return array_values(array_filter(self::$roleMap, $comparator, ARRAY_FILTER_USE_KEY));
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return $this->get('password');
    }

    /**
     * Sets the password for this user instance.
     *
     * NOTE: the password must be encrypted.
     *
     * Example:
     *   $encoder = $container->get('security.password_encoder');
     *   $encoded = $encoder->encodePassword($user, $plainPassword);
     *   $user->setPassword($encoded);
     *
     * @param string $encoded The encoded password.
     *
     * @return UserInformation
     */
    public function setPassword($encoded)
    {
        $this->set('password', $encoded);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {
        return $this->get('username');
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {
        $this->remove('password');
        $this->salt = null;
    }

    /**
     * Get the granted access levels.
     *
     * @return int
     */
    public function getAccessLevel()
    {
        return $this->get('acl', 0);
    }

    /**
     * Set the granted access levels.
     *
     * @param int $accessLevel The granted access levels.
     *
     * @return UserInformation
     */
    public function setAccessLevel($accessLevel)
    {
        $this->set('acl', $accessLevel);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccessLevel($accessLevel)
    {
        return $accessLevel === ($this->getAccessLevel() & $accessLevel);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function get($key, $default = null)
    {
        $key = strtolower($key);

        if (!array_key_exists($key, $this->data)) {
            if (null === $default) {
                return null;
            }

            return $default;
        }

        return $this->data[$key];
    }

    /**
     * Sets a value by name.
     *
     * @param string       $key   The key.
     *
     * @param string|array $value The value.
     *
     * @return UserInformation
     *
     * @api
     */
    public function set($key, $value)
    {
        $key = strtolower($key);

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function has($key)
    {
        return array_key_exists(strtolower($key), $this->data);
    }

    /**
     * Removes a value.
     *
     * @param string $key The value name.
     *
     * @return UserInformation
     *
     * @api
     */
    public function remove($key)
    {
        $key = strtolower($key);

        unset($this->data[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values()
    {
        return array_merge(
            [
                'acl'  => $this->getAccessLevel(),
                'salt' => $this->salt
            ],
            $this->data
        );
    }

    /**
     * String representation of this user information for use in logs.
     *
     * Examples may be: "user foo" or "token 0123456789".
     *
     * @return string
     */
    public function asString()
    {
        return 'authenticated';
    }
}
