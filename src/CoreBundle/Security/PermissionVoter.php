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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * This class checks the permissions of the authenticated user against the current request.
 */
class PermissionVoter implements VoterInterface
{
    /**
     * The router.
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * The request stack.
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Create a new instance.
     *
     * @param RouterInterface $router       The router component.
     *
     * @param RequestStack    $requestStack The request stack.
     */
    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router       = $router;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return 'ROLE_CHECK' === $attribute;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsClass($class)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!(($object instanceof Request) || $this->supportsAnyAttribute($attributes))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (!(($request = $object) instanceof Request)) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $route        = $this->router->getRouteCollection()->get($request->get('_route'));
        $requiredRole = $route->getOption('required_role');

        if (null === $requiredRole) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInformationInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        foreach ($user->getRoles() as $role) {
            if (strtoupper($role) == strtoupper($requiredRole)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }

    /**
     * Test if we support any of the attributes.
     *
     * @param string[] $attributes The attributes to test.
     *
     * @return bool
     */
    private function supportsAnyAttribute($attributes)
    {
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                return true;
            }
        }

        return false;
    }
}
