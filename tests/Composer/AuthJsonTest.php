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

namespace Tenside\Core\Test\Composer;

use Tenside\Core\Composer\AuthJson;
use Tenside\Core\Test\TestCase;

/**
 * This class tests the composer auth json handling.
 */
class AuthJsonTest extends TestCase
{
    /**
     * Test setting of a github token.
     *
     * @return void
     */
    public function testGithubOAuthToken()
    {
        $content = '{"github-oauth": {"github.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGithubOAuthToken());
        $this->assertSame($auth, $auth->setGithubOAuthToken('123456789'));
        $this->assertSame('123456789', $auth->getGithubOAuthToken());
        $this->assertSame($auth, $auth->removeGithubOAuthToken());
        $this->assertNull($auth->getGithubOAuthToken());
    }

    /**
     * Test setting of a github token with domain.
     *
     * @return void
     */
    public function testGithubOAuthTokenWithDomain()
    {
        $content = '{"github-oauth": {"not-github.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGithubOAuthToken('not-github.com'));
        $this->assertSame($auth, $auth->setGithubOAuthToken('123456789', 'not-github.com'));
        $this->assertSame('123456789', $auth->getGithubOAuthToken('not-github.com'));
        $this->assertSame($auth, $auth->removeGithubOAuthToken('not-github.com'));
        $this->assertNull($auth->getGithubOAuthToken('not-github.com'));
    }

    /**
     * Test setting of a gitlab token.
     *
     * @return void
     */
    public function testGitlabOAuthToken()
    {
        $content = '{"gitlab-oauth": {"gitlab.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGitlabOAuthToken());
        $this->assertSame($auth, $auth->setGitlabOAuthToken('123456789'));
        $this->assertSame('123456789', $auth->getGitlabOAuthToken());
        $this->assertSame($auth, $auth->removeGitlabOAuthToken());
        $this->assertNull($auth->getGitlabOAuthToken());
    }

    /**
     * Test setting of a gitlab token with domain.
     *
     * @return void
     */
    public function testGitlabOAuthTokenWithDomain()
    {
        $content = '{"gitlab-oauth": {"not-gitlab.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGitlabOAuthToken('not-gitlab.com'));
        $this->assertSame($auth, $auth->setGitlabOAuthToken('123456789', 'not-gitlab.com'));
        $this->assertSame('123456789', $auth->getGitlabOAuthToken('not-gitlab.com'));
        $this->assertSame($auth, $auth->removeGitlabOAuthToken('not-gitlab.com'));
        $this->assertNull($auth->getGitlabOAuthToken('not-gitlab.com'));
    }

    /**
     * Test setting of a gitlab token.
     *
     * @return void
     */
    public function testGitlabPrivateToken()
    {
        $content = '{"gitlab-token": {"gitlab.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGitlabPrivateToken());
        $this->assertSame($auth, $auth->setGitlabPrivateToken('123456789'));
        $this->assertSame('123456789', $auth->getGitlabPrivateToken());
        $this->assertSame($auth, $auth->removeGitlabPrivateToken());
        $this->assertNull($auth->getGitlabPrivateToken());
    }

    /**
     * Test setting of a gitlab token with domain.
     *
     * @return void
     */
    public function testGitlabPrivateTokenWithDomain()
    {
        $content = '{"gitlab-token": {"not-gitlab.com": "test"}}';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('test', $auth->getGitlabPrivateToken('not-gitlab.com'));
        $this->assertSame($auth, $auth->setGitlabPrivateToken('123456789', 'not-gitlab.com'));
        $this->assertSame('123456789', $auth->getGitlabPrivateToken('not-gitlab.com'));
        $this->assertSame($auth, $auth->removeGitlabPrivateToken('not-gitlab.com'));
        $this->assertNull($auth->getGitlabPrivateToken('not-gitlab.com'));
    }

    /**
     * Test setting of a bitbucket token.
     *
     * @return void
     */
    public function testBitbucketOAuthToken()
    {
        $content = '{
            "bitbucket-oauth": {"bitbucket.org": {"consumer-key": "authkey", "consumer-secret": "s3cret"}}
        }';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('authkey', $auth->getBitbucketOAuthKey());
        $this->assertSame('s3cret', $auth->getBitbucketOAuthSecret());
        $this->assertSame($auth, $auth->setBitbucketOAuth('123456789', '987654321'));
        $this->assertSame('123456789', $auth->getBitbucketOAuthKey());
        $this->assertSame('987654321', $auth->getBitbucketOAuthSecret());
        $this->assertSame($auth, $auth->removeBitbucketOAuth());
        $this->assertNull($auth->getGitlabPrivateToken());
    }

    /**
     * Test setting of a bitbucket token with domain.
     *
     * @return void
     */
    public function testBitbucketOAuthTokenWithDomain()
    {
        $content = '{
            "bitbucket-oauth": {"not-bitbucket.org": {"consumer-key": "authkey", "consumer-secret": "s3cret"}}
        }';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('authkey', $auth->getBitbucketOAuthKey('not-bitbucket.org'));
        $this->assertSame('s3cret', $auth->getBitbucketOAuthSecret('not-bitbucket.org'));
        $this->assertSame($auth, $auth->setBitbucketOAuth('123456789', '987654321', 'not-bitbucket.org'));
        $this->assertSame('123456789', $auth->getBitbucketOAuthKey('not-bitbucket.org'));
        $this->assertSame('987654321', $auth->getBitbucketOAuthSecret('not-bitbucket.org'));
        $this->assertSame($auth, $auth->removeBitbucketOAuth('not-bitbucket.org'));
        $this->assertNull($auth->getBitbucketOAuthKey('not-bitbucket.org'));
        $this->assertNull($auth->getBitbucketOAuthSecret('not-bitbucket.org'));
    }


    /**
     * Test setting of http-basic auth.
     *
     * @return void
     */
    public function testHttpBasic()
    {
        $content = '{
            "http-basic": {"contao.org": {"username": "horst", "password": "s3cret"}}
        }';
        $auth    = new AuthJson($this->createFixture('auth.json', $content));

        $this->assertSame('horst', $auth->getHttpBasicUser('contao.org'));
        $this->assertSame('s3cret', $auth->getHttpBasicPassword('contao.org'));
        $this->assertSame($auth, $auth->setHttpBasic('eugen', 'v3ry53cr3t', 'contao.org'));
        $this->assertSame('eugen', $auth->getHttpBasicUser('contao.org'));
        $this->assertSame('v3ry53cr3t', $auth->getHttpBasicPassword('contao.org'));
        $this->assertSame($auth, $auth->removeBitbucketOAuth());
        $this->assertNull($auth->getGitlabPrivateToken());
    }
}
