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
use Tenside\Config\SourceInterface;

/**
 * This class provides central authentication validation.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AuthRegistry
{
    // FIXME: this registry is hard coded currently.
    /**
     * All registered handler classes.
     *
     * @var string[]
     */
    private $providerClasses = [
        '\Tenside\Web\Auth\JwtValidator',
        '\Tenside\Web\Auth\AuthorizationFromConfig',
    ];

    /**
     * The provider instances.
     *
     * @var AuthInterface[]
     */
    private $instances;

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
     * Handle some authentication header value.
     *
     * @param Request $request The authentication header value.
     *
     * @return UserInformationInterface|null
     */
    public function handleAuthentication(Request $request)
    {
        foreach ($this->getProviders() as $provider) {
            if ($provider->supports($request)) {
                $userData = $provider->authenticate($request);
                if (null !== $userData) {
                    return $userData;
                }
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
        if (!isset($this->instances)) {
            $this->instances = [];
            foreach ($this->providerClasses as $class) {
                $this->instances[$class] = new $class($this->configSource);
            }
        }

        return $this->instances;
    }
}
