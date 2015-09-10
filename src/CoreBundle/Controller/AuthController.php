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

namespace Tenside\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Tenside\CoreBundle\Security\UserInformationInterface;

/**
 * The main entry point.
 */
class AuthController extends AbstractController
{
    /**
     * Try to validate the user from the request and return a jwt authentication result then.
     *
     * @return JsonResponse
     *
     * @throws \RuntimeException For invalid user classes.
     */
    public function checkAuthAction()
    {
        $user = $this->getUser();

        if (null !== $user) {
            if (!$user instanceof UserInformationInterface) {
                throw new \RuntimeException('Invalid user object');
            }
            $token = $this->get('tenside.jwt_authenticator')->getTokenForData($user);
            return new JsonResponse(
                [
                    'status' => 'ok',
                    'token'  => $token,
                    'acl'    => $user->getRoles(),
                ],
                JsonResponse::HTTP_OK,
                ['Authentication' => $token]
            );
        }

        return new JsonResponse(['status' => 'unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
