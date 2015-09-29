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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Tenside\CoreBundle\TensideJsonConfig;

/**
 * This class validates jwt.
 */
class JWTAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    /**
     * The client secret used for encoding and decoding.
     *
     * @var string
     */
    private $secret;

    /**
     * The local id.
     *
     * @var string
     */
    private $localId;

    /**
     * Create a new instance.
     *
     * @param TensideJsonConfig $config The configuration.
     *
     * @throws \LogicException When no secret has been defined.
     */
    public function __construct(TensideJsonConfig $config)
    {
        $this->secret  = $config->getSecret();
        $this->localId = $config->getLocalDomain();
    }

    /**
     * Create a token from the passed user information.
     *
     * @param UserInformationInterface $userData The user data to issue a token for.
     *
     * @param int|null                 $lifetime The lifetime in seconds this token shall be valid.
     *                                           Use null for no limit.
     *
     * @return string
     */
    public function getTokenForData(UserInformationInterface $userData, $lifetime = 3600)
    {
        return $this->encode($userData->values(), $lifetime);
    }

    /**
     * Create the token.
     *
     * @param Request $request     The request being processed.
     *
     * @param string  $providerKey The provider key.
     *
     * @return JavascriptWebToken
     *
     * @throws AuthenticationException For any invalid token.
     */
    public function createToken(Request $request, $providerKey)
    {
        if (!$this->secret) {
            return null;
        }

        // look for an authorization header
        $authorizationHeader = $request->headers->get('Authorization');

        if ($authorizationHeader === null) {
            throw new AuthenticationException('No authorization header provided');
        }

        if (substr($authorizationHeader, 0, 6) !== 'Bearer') {
            return null;
        }

        // extract the JWT
        $authToken = str_replace('Bearer ', '', $authorizationHeader);

        try {
            // decode and validate the JWT - will throw exceptions for various conditions.
            $token = $this->decodeToken($authToken);
        } catch (\Exception $exception) {
            throw new AuthenticationException($exception->getMessage(), 0, $exception);
        }

        return new JavascriptWebToken($token, $providerKey);
    }

    /**
     * Authenticate the passed token.
     *
     * @param TokenInterface        $token        The token to authenticate.
     *
     * @param UserProviderInterface $userProvider The user provider.
     *
     * @param string                $providerKey  The provider key.
     *
     * @return JavascriptWebToken
     *
     * @throws \LogicException When no secret is in the config and therefore the token can not be authenticated.
     *
     * @throws AuthenticationException When the token is invalid.
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$this->secret) {
            throw new \LogicException('Config does not contain a secret.');
        }

        if ($token->getCredentials() === null) {
            throw new AuthenticationException(sprintf('Invalid token.'));
        }

        // Get the user for the injected UserProvider
        $user = $userProvider->loadUserByUsername($token->getCredentials()->username);

        if (!$user) {
            throw new AuthenticationException(sprintf('Invalid token.'));
        }

        return new JavascriptWebToken($token->getCredentials(), $providerKey, $user, $user->getRoles());
    }

    /**
     * Test if we support the token.
     *
     * @param TokenInterface $token       The token to test.
     *
     * @param string         $providerKey The provider key.
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return ($token instanceof JavascriptWebToken) && ($token->getProviderKey() === $providerKey);
    }

    /**
     * Generate a proper "unauthorized" response.
     *
     * @param Request                 $request   The request to generate the response for.
     *
     * @param AuthenticationException $exception The exception to generate the response for.
     *
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new Response('Authentication Failed: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Decode a token.
     *
     * @param string $jwt The jwt as string.
     *
     * @return object
     *
     * @throws \UnexpectedValueException When the token does not match the local id.
     */
    private function decodeToken($jwt)
    {
        // Decode the token.
        $decodedToken = \JWT::decode($jwt, $this->secret, ['HS256']);
        // Validate that this JWT was made for us.
        if ($this->localId && ($decodedToken->aud != $this->localId)) {
            throw new \UnexpectedValueException('This token is not intended for us.');
        }

        return $decodedToken;
    }

    /**
     * Encode a token.
     *
     * @param null|array $customPayload Any custom payload to be added to the token.
     *
     * @param int|null   $lifetime      The lifetime in seconds this token shall be valid. Use null for no limit.
     *
     * @return string
     */
    private function encode($customPayload = null, $lifetime = 3600)
    {
        $time    = time();
        $payload = ['iat' => $time];

        if (null !== $customPayload) {
            $payload = array_merge($customPayload, $payload);
        }

        $jti = md5(json_encode($payload));

        $payload['jti'] = $jti;

        if (null !== $this->localId) {
            $payload['aud'] = $this->localId;
        }

        if (null !== $lifetime) {
            $payload['exp'] = ($time + $lifetime);
        }

        $jwt = \JWT::encode($payload, $this->secret);

        return $jwt;
    }
}
