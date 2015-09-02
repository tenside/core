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
use Tenside\Web\Auth\AuthorizationFromConfig;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Test the application.
 */
class AuthorizationFromConfigTest extends TestCase
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
     * @param string $username The username to use.
     *
     * @param string $password The password to use.
     *
     * @param string $scheme   The scheme to use.
     *
     * @return Request
     */
    private function authRequestByUserAndPass($username, $password, $scheme = 'TensideBasic')
    {
        return $this->authRequest($scheme . ' ' . base64_encode($username . ':' . $password));
    }

    /**
     * Test that preferred scheme is supported.
     *
     * @return void
     */
    public function testSupportsReportedScheme()
    {
        $provider = new AuthorizationFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        list($scheme) = explode(' ', $provider->getChallenge());

        $this->assertTrue($provider->supports($this->authRequestByUserAndPass('testuser', 'testpassword', $scheme)));
    }

    /**
     * Test that scheme TensideBasic is supported.
     *
     * @return void
     */
    public function testSupportsTensideBasic()
    {
        $provider = new AuthorizationFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        $this->assertTrue($provider->supports($this->authRequestByUserAndPass('testuser', 'testpassword')));
    }

    /**
     * Test that scheme basic is supported.
     *
     * @return void
     */
    public function testSupportsBasic()
    {
        $provider = new AuthorizationFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        $this->assertTrue($provider->supports($this->authRequestByUserAndPass('testuser', 'testpassword', 'Basic')));
    }

    /**
     * Test that some arbitrary schemes are not supported.
     *
     * @return void
     */
    public function testDoesNotSupportInvalidRequests()
    {
        $provider = new AuthorizationFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        $this->assertFalse($provider->supports(new Request()));
        $this->assertNull($provider->authenticate(new Request()));
        $this->assertFalse($provider->supports($this->authRequestByUserAndPass('testuser', 'testpassword', 'Digest')));
        $this->assertNull($provider->authenticate($this->authRequestByUserAndPass('testuser', 'testpassword', 'Digest')));
        $this->assertFalse($provider->supports($this->authRequest('Arbitrary')));
        $this->assertNull($provider->authenticate($this->authRequest('Arbitrary')));
        $this->assertFalse($provider->supports($this->authRequest('TensideBasic invalid')));
        $this->assertNull($provider->authenticate($this->authRequest('TensideBasic invalid')));
        $this->assertFalse($provider->supports($this->authRequest('TensideBasic ' . base64_encode('invalid'))));
        $this->assertNull($provider->authenticate($this->authRequest('TensideBasic ' . base64_encode('invalid'))));
    }

    /**
     * Test the whole functionality.
     *
     * @return void
     */
    public function testFunctionality()
    {
        $provider = new AuthorizationFromConfig(new SourceJson($this->getTempFile('tenside.json')));
        $request1 = $this->authRequestByUserAndPass('testuser', 'testpassword');
        $request2 = $this->authRequestByUserAndPass('testuser', 'wrongpassword');

        $this->assertSame(
            $provider,
            $provider->addUser('testuser', 'testpassword', UserInformationInterface::ACL_UPGRADE)
        );
        $this->assertTrue($provider->supports($request1));
        $user = $provider->authenticate($request1);
        $this->assertInstanceOf('Tenside\Web\Auth\UserInformationInterface', $user);
        $this->assertEquals('testuser', $user->get('user'));

        $this->assertTrue($provider->supports($request2));
        $this->assertNull($provider->authenticate($request2));

        $this->assertSame($provider, $provider->removeUser('testuser'));
        $this->assertTrue($provider->supports($request1));
        $this->assertNull($provider->authenticate($request1));
    }
}
