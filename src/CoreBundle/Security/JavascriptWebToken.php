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

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * This class is a simple implementation of a Javascript Web Token.
 */
class JavascriptWebToken extends AbstractToken
{
    /**
     * The token.
     *
     * @var string
     */
    private $token;

    /**
     * The provider key.
     *
     * @var string
     */
    private $providerKey;

    /**
     * Constructor.
     *
     * @param string                   $token       The user credentials
     *
     * @param string                   $providerKey The provider key
     *
     * @param string|object            $user        The user
     *
     * @param RoleInterface[]|string[] $roles       An array of roles
     *
     * @throws \InvalidArgumentException When the provider key is empty.
     */
    public function __construct($token, $providerKey, $user = 'anon.', array $roles = [])
    {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->setUser($user);
        $this->token       = $token;
        $this->providerKey = $providerKey;

        if ($roles) {
            $this->setAuthenticated(true);
        }
    }

    /**
     * Returns the provider key.
     *
     * @return string The provider key
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        parent::eraseCredentials();

        $this->token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->token, $this->providerKey, parent::serialize()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($this->token, $this->providerKey, $parentStr) = unserialize($str);
        parent::unserialize($parentStr);
    }
}
