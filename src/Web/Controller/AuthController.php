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

namespace Tenside\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Config\SourceSubSection;
use Tenside\Web\Auth\AuthFromConfig;
use Tenside\Web\Auth\AuthInterface;
use Tenside\Web\Auth\AuthUserPasswordInterface;

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
        static::createRoute($routes, 'checkAuth', '/auth', __CLASS__, ['GET']);
        static::createRoute($routes, 'validateLogin', '/auth', __CLASS__, ['POST']);
        static::createRoute($routes, 'logout', '/auth', __CLASS__, ['DELETE']);
    }

    /**
     * Build the auth provider list.
     *
     * @return AuthInterface[]
     */
    protected function buildAuthProviderList()
    {
        return [
            // FIXME: add functionality to add more auth providers.
            new AuthFromConfig(new SourceSubSection($this->getTenside()->getConfigSource(), 'auth-password'))
        ];
    }

    /**
     * Build the auth response array for JsonResponses.
     *
     * @return JsonResponse
     */
    protected function buildAuthResponse()
    {
        $array = array('id' => $this->getApplication()->getSession()->getId());
        $user  = $this->getApplication()->getAuthenticatedUser();
        if ($user) {
            $array['user'] = $user->asArray();
        }

        return new JsonResponse($array, $user ? 200 : 401);
    }

    /**
     * Check if there is currently an authenticated session and if so, return true.
     *
     * @return JsonResponse
     */
    protected function checkAuthAction()
    {
        return $this->buildAuthResponse();
    }

    /**
     * Check if there is currently an authenticated session and if so, return true.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     */
    protected function validateLoginAction(Request $request)
    {
        $this->getApplication()->setAuthenticatedUser(null);

        $credentials = json_decode($request->getContent(), true);

        $userData = null;
        foreach ($this->buildAuthProviderList() as $provider) {
            if ($provider instanceof AuthUserPasswordInterface) {
                if (isset($credentials['username']) && isset($credentials['password'])) {
                    $userData = $provider->validate($credentials['username'], $credentials['password']);
                }
            }

            if (null !== $userData) {
                break;
            }
        }

        if (null !== $userData) {
            $this->getApplication()->setAuthenticatedUser($userData);
        }

        return $this->buildAuthResponse();
    }

    /**
     * Check if there is currently an authenticated session and if so, return true.
     *
     * @return JsonResponse
     */
    protected function logoutAction()
    {
        $this->getApplication()->getSession()->invalidate();
        return new JsonResponse([], 200);
    }
}
