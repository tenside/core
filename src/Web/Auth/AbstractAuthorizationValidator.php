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
use Tenside\Config\SourceInterface;

/**
 * Authentication provider that reads the data from a config file.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class AbstractAuthorizationValidator implements AuthInterface
{
    /**
     * The config source to use.
     *
     * @var SourceInterface
     */
    protected $configSource;

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
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        // No auth header? get out!
        if (!$request->headers->has('authorization')) {
            return false;
        }

        list($scheme, $data) = explode(' ', $request->headers->get('authorization'));

        return $this->supportsScheme($scheme, $data);
    }

    /**
     * Validate the authentication data given in request.
     *
     * @param Request $request The request to check.
     *
     * @return UserInformationInterface|null
     */
    public function authenticate(Request $request)
    {
        // No auth header? get out!
        if (!$request->headers->has('authorization')) {
            return null;
        }

        list($scheme, $data) = explode(' ', $request->headers->get('authorization'));

        return $this->authenticateScheme($scheme, $data);
    }

    /**
     * Check if this handler supports the given scheme.
     *
     * @param string $scheme The scheme to check.
     *
     * @param string $data   The payload from the authentication header.
     *
     * @return bool
     */
    abstract protected function supportsScheme($scheme, $data);

    /**
     * Validate the content of an HTTP Authenticate header.
     *
     * @param string $scheme The scheme to check.
     *
     * @param string $data   The payload from the authentication header.
     *
     * @return UserInformationInterface|null
     */
    abstract protected function authenticateScheme($scheme, $data);
}
