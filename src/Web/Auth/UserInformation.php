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
 * A user information..
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class UserInformation implements UserInformationInterface, \IteratorAggregate, \Countable
{
    // @codingStandardsIgnoreStart
    /**
     * String representation map for access levels.
     *
     * @var array
     */
    public static $ACL_NAMES = [
        UserInformationInterface::ACL_UPGRADE => 'upgrade',
        UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS => 'manipulate-requirements',
        UserInformationInterface::ACL_EDIT_COMPOSER_JSON => 'edit-composer-json'
    ];
    // @codingStandardsIgnoreEnd

    /**
     * The contained data.
     *
     * @var array
     */
    private $data = [];

    /**
     * Constructor.
     *
     * @param array $data An array of values.
     *
     * @api
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
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
     * Check if the user has the given access level.
     *
     * @param int $accessLevel The access level to check.
     *
     * @return bool
     */
    public function hasAccessLevel($accessLevel)
    {
        if (!$this->has('acl')) {
            return false;
        }

        return $accessLevel === ($this->getAccessLevel() & $accessLevel);
    }

    /**
     * Returns the parameter keys.
     *
     * @return array<integer|string> An array of parameter keys
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Returns a value by name.
     *
     * @param string $key     The value name.
     *
     * @param mixed  $default The default value.
     *
     * @return mixed|null The value if found, null otherwise.
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
     * Returns true if the HTTP header is defined.
     *
     * @param string $key The key.
     *
     * @return bool true if the value exists, false otherwise.
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
     * Returns an iterator for headers.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Returns the number of headers.
     *
     * @return int The number of headers
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function values()
    {
        return array_merge(
            [
                'acl' => $this->getAccessLevel()
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
