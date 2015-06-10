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

namespace Tenside\Web\Auth;

use Symfony\Component\HttpFoundation\Request;

/**
 * This interface is an base for auth providers.
 */
interface AuthInterface
{
    /**
     * Checking method for determining if the implementing class supports the authentication data given in request.
     *
     * @param Request $request The request to check.
     *
     * @return bool
     */
    public function supports(Request $request);

    /**
     * Retrieve a challenge to use as "WWW-Authenticate" header challenge.
     *
     * @return string
     *
     * @see    supports()
     */
    public function getChallenge();

    /**
     * Validate the authentication data given in request.
     *
     * @param Request $request The request to check.
     *
     * @return UserInformationInterface|null
     */
    public function authenticate(Request $request);
}
