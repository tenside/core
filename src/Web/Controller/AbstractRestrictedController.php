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

use Symfony\Component\HttpFoundation\Request;
use Tenside\Web\Exception\LoginRequiredException;
use Tenside\Web\UserInformation;

/**
 * Abstract controller class that needs authentication.
 */
abstract class AbstractRestrictedController extends AbstractController
{
    /**
     * {@inheritDoc}
     */
    public function handle(Request $request)
    {
        $this->checkAccess();

        return parent::handle($request);
    }

    /**
     * Check if the user has access to the controller.
     *
     * @return void
     *
     * @throws LoginRequiredException When no user is logged in or the access level is too low.
     */
    protected function checkAccess()
    {
        $user = $this->getApplication()->getAuthenticatedUser();
        if (null === $user) {
            throw new LoginRequiredException('Login required');
        }

        if (!$user->hasRole($this->getRole())) {
            throw new LoginRequiredException('Insufficient rights.');
        }
    }

    /**
     * Retrieve the needed role.
     *
     * @return string
     */
    protected function getRole()
    {
        return UserInformation::ROLE_ALL;
    }
}
