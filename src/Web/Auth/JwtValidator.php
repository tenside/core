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

use JWT;

/**
 * Validates and issues jwt tokens used by calling the API.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class JwtValidator extends AbstractAuthorizationValidator implements TokenValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    protected function supportsScheme($scheme, $data)
    {
        return ('jwt' === strtolower($scheme)) && (false !== strpos($data, 'token='));
    }

    /**
     * {@inheritdoc}
     */
    public function getChallenge()
    {
        return 'jwt realm="jwt protected"';
    }

    /**
     * {@inheritdoc}
     */
    protected function authenticateScheme($scheme, $data)
    {
        // extract token.
        if (!preg_match('#token="?([^" ]+)"?#', $data, $match)) {
             return null;
        }
        $token = $match[1];

        try {
            $decrypted = (array) JWT::decode($token, $this->getPrivateKey(), ['HS256']);
        } catch (\Exception $exception) {
            return null;
        }

        if (empty($decrypted['acl'])) {
            return null;
        }

        return new UserInformation($decrypted);
    }

    /**
     * Create a token from the passed user information.
     *
     * @param UserInformationInterface $userData     The user data to issue a token for.
     *
     * @param null|int                 $invalidAfter Optional timestamp after when the token shall be invalid.
     *
     * @return string
     */
    public function getTokenForData($userData, $invalidAfter = null)
    {
        $token = $userData->values();
        if (null !== $invalidAfter) {
            $token['iad'] = $invalidAfter;
        }

        return JWT::encode($token, $this->getPrivateKey());
    }

    /**
     * Retrieve the private key from the config.
     *
     * @return string
     *
     * @throws \LogicException When the config does not hold any secret.
     */
    private function getPrivateKey()
    {
        if (!$this->configSource->has('secret')) {
            throw new \LogicException('Config does not contain a secret.');
        }

        return $this->configSource->get('secret');
    }
}