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
use Tenside\Test\TestCase;
use Tenside\Web\Auth\AuthInterface;
use Tenside\Web\Auth\AuthRegistry;
use Tenside\Web\Auth\UserInformation;

/**
 * Test the application.
 */
class AuthRegistryTest extends TestCase
{
    /**
     * Mock an auth provider which returns the passed user when the challenge has been met.
     *
     * @param string          $challenge The challenge.
     *
     * @param UserInformation $user      The user object to return.
     *
     * @return AuthInterface
     */
    private function mockProvider($challenge, $user)
    {
        $provider = $this->getMockForAbstractClass('Tenside\Web\Auth\AuthInterface');
        $provider->method('supports')->willReturnCallback(function (Request $request) use ($challenge) {
            return $challenge === $request->headers->get('Authorization');
        });
        $provider->method('getChallenge')->willReturn($challenge);
        $provider->method('authenticate')->willReturnCallback(function (Request $request) use ($challenge, $user) {
            if ($challenge === $request->headers->get('Authorization')) {
                return $user;
            }

            return null;
        });

        return $provider;
    }


    /**
     * Test the has access level method.
     *
     * @return void
     */
    public function testAuthentication()
    {
        $user      = new UserInformation();
        $provider1 = $this->mockProvider('Challenge 1', $user);
        $provider2 = $this->mockProvider('Challenge 2', $user);
        $registry  = new AuthRegistry([$provider1, $provider2]);

        $request = new Request();
        $request->headers->set('Authorization', 'Challenge 2');

        $this->assertEquals(['Challenge 1', 'Challenge 2'], $registry->buildChallengeList());
        $this->assertEquals($user, $registry->handleAuthentication($request));
    }
}
