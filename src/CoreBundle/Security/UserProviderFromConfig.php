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

namespace Tenside\CoreBundle\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Tenside\Config\SourceInterface;

/**
 * This class validates jwt.
 */
class UserProviderFromConfig implements UserProviderInterface
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
     *
     * @throws UsernameNotFoundException When the user is not contained.
     */
    public function loadUserByUsername($username)
    {
        if (!$this->configSource->has('auth-password/' . $username)) {
            throw new UsernameNotFoundException();
        }

        $userData = $this->configSource->get('auth-password/' . $username);

        return new UserInformation(array_merge($userData, ['username' => $username]));
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnsupportedUserException For invalid user types.
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof UserInformation) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $class === 'Tenside\\CoreBundle\\Security\\UserInformation';
    }

    /**
     * Add/update the passed credentials in the database.
     *
     * @param UserInformation $user The user to add.
     *
     * @return UserProviderFromConfig
     */
    public function addUser($user)
    {
        $this->configSource->set('auth-password/' . $user->getUsername(), $user->values());

        return $this;
    }

    /**
     * Add the passed credentials in the database.
     *
     * @param string|UserInformation $user The username to remove.
     *
     * @return UserProviderFromConfig
     */
    public function removeUser($user)
    {
        $username = ($user instanceof UserInformation) ? $user->getUsername() : (string) $user;

        $this->configSource->set('auth-password/' . $username, null);

        return $this;
    }
}
