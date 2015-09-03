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

namespace Tenside\Test\Web;

use Symfony\Component\HttpFoundation\Request;
use Tenside\Config\SourceJson;
use Tenside\Test\TestCase;
use Tenside\Web\Auth\JwtValidator;
use Tenside\Web\Auth\UserInformation;

/**
 * Test the application.
 */
class JwtValidatorTest extends TestCase
{
    /**
     * Create a request for basic auth.
     *
     * @param string $auth The auth header to use.
     *
     * @return Request
     */
    private function authRequest($auth)
    {
        $request = new Request();
        $request->headers->set('Authorization', $auth);

        return $request;
    }

    /**
     * Create a request for basic auth.
     *
     * @param string $token  The username to use.
     *
     * @param string $scheme The scheme to use.
     *
     * @return Request
     */
    private function authRequestByToken($token, $scheme = 'jwt')
    {
        return $this->authRequest($scheme . ' token=' . $token);
    }

    /**
     * Test that preferred scheme is supported.
     *
     * @return void
     */
    public function testSupportsReportedScheme()
    {
        $provider = new JwtValidator(new SourceJson($this->getTempFile('tenside.json')));

        list($scheme) = explode(' ', $provider->getChallenge());

        $this->assertTrue($provider->supports($this->authRequestByToken('token', $scheme)));
    }

    /**
     * Test that some arbitrary schemes are not supported.
     *
     * @return void
     */
    public function testDoesNotSupportInvalidRequests()
    {
        $config = new SourceJson($this->getTempFile('tenside.json'));
        $config->set('secret', 'confidential');
        $provider = new JwtValidator($config);

        $this->assertNull($provider->authenticate(new Request()));
        $this->assertNull($provider->authenticate($this->authRequest('Arbitrary')));
        $this->assertNull($provider->authenticate($this->authRequest('jwt invalid')));
        $this->assertNull($provider->authenticate($this->authRequest('TensideBasic ' . base64_encode('user:pass'))));
    }

    /**
     * Test that an exception is thrown when no secret is available.
     *
     * @return void
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Config does not contain a secret.
     */
    public function testThrowsExceptionWhenNoSecretAvailable()
    {
        $provider = new JwtValidator(new SourceJson($this->getTempFile('tenside.json')));
        $user     = new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_ALL]);

        $provider->getTokenForData($user);
    }

    /**
     * Test that authenticate() returns null when the jwt is invalid and therefore an exception has been thrown.
     *
     * @return void
     */
    public function testReturnsNullForInvalidTokens()
    {
        $config = new SourceJson($this->getTempFile('tenside.json'));
        $config->set('secret', 'confidential');
        $provider = new JwtValidator($config);
        $user     = new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_ALL]);

        $token = $provider->getTokenForData($user);

        $this->assertNull($provider->authenticate($this->authRequestByToken($token . 'breakthetoken')));
    }

    /**
     * Test that authenticate() returns null when the jwt is invalid and therefore an exception has been thrown.
     *
     * @return void
     */
    public function testReturnsNullForUserDataWithoutAcl()
    {
        $config = new SourceJson($this->getTempFile('tenside.json'));
        $config->set('secret', 'confidential');
        $provider = new JwtValidator($config);
        $user     = $this->getMock('Tenside\Web\Auth\UserInformationInterface');
        $user->method('values')->willReturn(['user' => 'username']);

        $token = $provider->getTokenForData($user);

        $this->assertNull($provider->authenticate($this->authRequestByToken($token)));
    }

    /**
     * Test that authenticate() returns null when the jwt is expired.
     *
     * @return void
     */
    public function testReturnsNullForExpiredToken()
    {
        $config = new SourceJson($this->getTempFile('tenside.json'));
        $config->set('secret', 'confidential');
        $provider = new JwtValidator($config);
        $user     = new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_ALL]);

        $token = $provider->getTokenForData($user, (time() - 3600));

        $this->assertNull($provider->authenticate($this->authRequestByToken($token)));
    }

    /**
     * Test the whole functionality.
     *
     * @return void
     */
    public function testFunctionality()
    {
        $config = new SourceJson($this->getTempFile('tenside.json'));
        $config->set('secret', 'confidential');

        $provider = new JwtValidator($config);
        $user     = new UserInformation(['user' => 'testuser', 'acl' => UserInformation::ACL_ALL]);

        $token = $provider->getTokenForData($user);

        $this->assertInternalType('string', $token);

        $user2 = $provider->authenticate($this->authRequestByToken($token));

        foreach ($user as $key => $value) {
            $this->assertTrue($user2->has($key));
            $this->assertEquals($value, $user2->get($key));
        }
    }
}
