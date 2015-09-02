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

use Symfony\Component\HttpFoundation\Request;

/**
 * This class provides central authentication validation.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AuthRegistry
{
    /**
     * The provider instances.
     *
     * @var AuthInterface[]
     */
    private $providers;

    /**
     * Create a new instance.
     *
     * @param AuthInterface[] $providers The authorization providers to use.
     */
    public function __construct($providers)
    {
        $this->providers = $providers;
    }

    /**
     * Handle some authentication header value.
     *
     * @param Request $request The authentication header value.
     *
     * @return UserInformationInterface|null
     */
    public function handleAuthentication(Request $request)
    {
        foreach ($this->getProviders() as $provider) {
            if ($provider->supports($request) && null !== ($userData = $provider->authenticate($request))) {
                return $userData;
            }
        }

        return null;
    }

    /**
     * Build the challenge list.
     *
     * @return string[]
     */
    public function buildChallengeList()
    {
        $challenges = [];
        foreach ($this->getProviders() as $provider) {
            $challenges[] = $provider->getChallenge();
        }

        return $challenges;
    }

    /**
     * Retrieve the provider instances.
     *
     * @return AuthInterface[]
     */
    private function getProviders()
    {
        return $this->providers;
    }
}
