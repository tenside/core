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

namespace Tenside\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Web\Auth\JwtValidator;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * The main entry point.
 */
class AuthController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'checkAuth', '/api/v1/auth', ['GET']);
    }

    /**
     * Try to validate the user from the request and return a jwt authentication result then.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     */
    protected function checkAuthAction(Request $request)
    {
        $registry        = $this->getApplication()->getAuthRegistry();
        $userInformation = $registry->handleAuthentication($request);
        if (null !== $userInformation) {
            $validator = new JwtValidator($this->getTenside()->getConfigSource());
            $token     = $validator->getTokenForData($userInformation, (time() + 3600));
            // FIXME: token only valid one hour, might want to increase this.

            return new JsonResponse([
                'status' => 'ok',
                'token' => $token,
                'acl' => $this->getAccessLevelList($userInformation),
            ]);
        }

        return new JsonResponse(
            ['status' => 'unauthorized'],
            JsonResponse::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => $registry->buildChallengeList()]
        );
    }

    /**
     * Create a string list of granted access levels.
     *
     * @param UserInformationInterface $userInformation The user information object for which the list shall be created.
     *
     * @return string[]
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function getAccessLevelList(UserInformationInterface $userInformation)
    {
        return array_values(
            array_filter(
                [
                    UserInformationInterface::ACL_UPGRADE                 => 'upgrade',
                    UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS => 'manipulate-requirements',
                    UserInformationInterface::ACL_EDIT_COMPOSER_JSON      => 'edit-composer-json',
                    UserInformationInterface::ACL_EDIT_APPKERNEL          => 'edit-app-kernel',
                ],
                function ($level) use ($userInformation) {
                    return $userInformation->hasAccessLevel($level);
                },
                ARRAY_FILTER_USE_KEY
            )
        );
    }
}
