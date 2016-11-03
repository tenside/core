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

namespace Tenside\Core\Composer;

use Tenside\Core\Util\JsonFile;

/**
 * This class abstracts the auth.json file manipulation.
 */
class AuthJson extends JsonFile
{
    /**
     * Const for github oauth tokens.
     */
    const GITHUB_OAUTH_KEY = 'github-oauth';

    /**
     * Const for gitlab oauth tokens.
     */
    const GITLAB_OAUTH_KEY = 'gitlab-oauth';

    /**
     * Const for gitlab private tokens.
     */
    const GITLAB_PRIVATE_KEY = 'gitlab-token';

    /**
     * Const for bitbucket tokens.
     */
    const BITBUCKET_OAUTH_KEY = 'bitbucket-oauth';

    /**
     * Const for htt-basic auth.
     */
    const HTTP_BASIC_KEY = 'http-basic';

    /**
     * Set a github OAuth token.
     *
     * @param string $token  The token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function setGithubOAuthToken($token, $domain = 'github.com')
    {
        $this->set(self::GITHUB_OAUTH_KEY . '/' . $this->escape($domain), $token);

        return $this;
    }

    /**
     * Get a github OAuth token.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getGithubOAuthToken($domain = 'github.com')
    {
        return $this->get(self::GITHUB_OAUTH_KEY . '/' . $this->escape($domain));
    }

    /**
     * Unset a github OAuth token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function removeGithubOAuthToken($domain = 'github.com')
    {
        $this->remove(self::GITHUB_OAUTH_KEY . '/' . $this->escape($domain));

        return $this;
    }

    /**
     * Set a gitlab OAuth token.
     *
     * @param string $token  The token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function setGitlabOAuthToken($token, $domain = 'gitlab.com')
    {
        $this->set(self::GITLAB_OAUTH_KEY . '/' . $this->escape($domain), $token);

        return $this;
    }

    /**
     * Get a gitlab OAuth token.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getGitlabOAuthToken($domain = 'gitlab.com')
    {
        return $this->get(self::GITLAB_OAUTH_KEY . '/' . $this->escape($domain));
    }

    /**
     * Unset a gitlab OAuth token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function removeGitlabOAuthToken($domain = 'gitlab.com')
    {
        $this->remove(self::GITLAB_OAUTH_KEY . '/' . $this->escape($domain));

        return $this;
    }

    /**
     * Set a gitlab private token.
     *
     * @param string $token  The token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function setGitlabPrivateToken($token, $domain = 'gitlab.com')
    {
        $this->set(self::GITLAB_PRIVATE_KEY . '/' . $this->escape($domain), $token);

        return $this;
    }

    /**
     * Get a gitlab private token.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getGitlabPrivateToken($domain = 'gitlab.com')
    {
        return $this->get(self::GITLAB_PRIVATE_KEY . '/' . $this->escape($domain));
    }

    /**
     * Unset a gitlab private token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function removeGitlabPrivateToken($domain = 'gitlab.com')
    {
        $this->remove(self::GITLAB_PRIVATE_KEY . '/' . $this->escape($domain));

        return $this;
    }

    /**
     * Set Bitbucket OAuth.
     *
     * @param string $key    The consumer key.
     *
     * @param string $secret The consumer secret.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function setBitbucketOAuth($key, $secret, $domain = 'bitbucket.org')
    {
        $this->set(
            self::BITBUCKET_OAUTH_KEY . '/' . $this->escape($domain),
            ['consumer-key' => $key, 'consumer-secret' => $secret]
        );

        return $this;
    }

    /**
     * Get Bitbucket OAuth key.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getBitbucketOAuthKey($domain = 'bitbucket.org')
    {
        return $this->get(self::BITBUCKET_OAUTH_KEY . '/' . $this->escape($domain) . '/consumer-key');
    }

    /**
     * Get Bitbucket OAuth secret.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getBitbucketOAuthSecret($domain = 'bitbucket.org')
    {
        return $this->get(self::BITBUCKET_OAUTH_KEY . '/' . $this->escape($domain) . '/consumer-secret');
    }

    /**
     * Unset a Bitbucket token.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function removeBitbucketOAuth($domain = 'bitbucket.org')
    {
        $this->remove(self::BITBUCKET_OAUTH_KEY . '/' . $this->escape($domain));

        return $this;
    }

    /**
     * Set http-basic auth.
     *
     * @param string $username The consumer key.
     *
     * @param string $password The consumer secret.
     *
     * @param string $domain   The domain.
     *
     * @return AuthJson
     */
    public function setHttpBasic($username, $password, $domain)
    {
        $this->set(
            self::HTTP_BASIC_KEY . '/' . $this->escape($domain),
            ['username' => $username, 'password' => $password]
        );

        return $this;
    }

    /**
     * Get http-basic auth user name.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getHttpBasicUser($domain)
    {
        return $this->get(self::HTTP_BASIC_KEY . '/' . $this->escape($domain) . '/username');
    }

    /**
     * Get http-basic auth password.
     *
     * @param string $domain The domain.
     *
     * @return string|null
     */
    public function getHttpBasicPassword($domain)
    {
        return $this->get(self::HTTP_BASIC_KEY . '/' . $this->escape($domain) . '/password');
    }

    /**
     * Unset http-basic auth.
     *
     * @param string $domain The domain.
     *
     * @return AuthJson
     */
    public function removeHttpBasic($domain)
    {
        $this->remove(self::HTTP_BASIC_KEY . '/' . $this->escape($domain));

        return $this;
    }
}
