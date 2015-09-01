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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Abstract controller class that needs authentication.
 */
abstract class AbstractRestrictedController extends AbstractController
{
    /**
     * The user information of the current request (if any).
     *
     * @var UserInformationInterface
     */
    private $userInformation;

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request)
    {
        $this->loadUserData($request);

        return parent::handle($request);
    }

    /**
     * Check if the user has access to the controller.
     *
     * @param Request $request The request to process.
     *
     * @return void
     */
    private function loadUserData(Request $request)
    {
        $registry        = $this->getApplication()->getAuthRegistry();
        $userInformation = $registry->handleAuthentication($request);
        if (null !== $userInformation) {
            $this->userInformation = $userInformation;
        }
    }

    /**
     * Ensure the needed access level is granted to the current user.
     *
     * @param int $accessLevel The access level that is needed.
     *
     * @return void
     *
     * @throws AccessDeniedHttpException When the access level has not been granted.
     * @throws UnauthorizedHttpException When the request is not authenticated.
     */
    protected function needAccessLevel($accessLevel)
    {
        if (null === $this->userInformation) {
            $registry = $this->getApplication()->getAuthRegistry();
            throw new UnauthorizedHttpException(
                $registry->buildChallengeList(),
                'Login required'
            );
        }

        if (!$this->userInformation->hasAccessLevel($accessLevel)) {
            throw new AccessDeniedHttpException('Insufficient rights.');
        }
    }
}
