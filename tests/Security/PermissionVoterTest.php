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

namespace Tenside\Core\Test\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tenside\Core\Security\PermissionVoter;
use Tenside\Core\Security\UserInformation;

/**
 * Test the application.
 */
class PermissionVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock a request for a route.
     *
     * @param string $routeName The name of the route.
     *
     * @return Request
     */
    protected function mockRequest($routeName = 'test-route')
    {
        return new Request([], [], ['_route' => $routeName]);
    }

    /**
     * Create a voter.
     *
     * @param string $role The role the route shall require.
     *
     * @return PermissionVoter
     */
    protected function mockVoter($role = null)
    {
        $stack      = new RequestStack();
        $collection = new RouteCollection();
        $collection->add('test-route', new Route('', [], [], ['required_role' => $role]));
        $stack->push($this->mockRequest('test-route'));

        $router = $this
            ->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->setMethods(['getRouteCollection'])
            ->getMockForAbstractClass();
        $router->method('getRouteCollection')->willReturn($collection);

        return new PermissionVoter($router, $stack);
    }

    /**
     * Mock a token for the given user.
     *
     * @param array|UserInterface|object|string $userRolesOrUser Anything to be used as user.
     *
     * @return TokenInterface
     *
     * @throws \InvalidArgumentException When the argument is invalid.
     */
    protected function mockToken($userRolesOrUser = null)
    {
        if (null === $userRolesOrUser) {
            $user = null;
        } elseif (is_object($userRolesOrUser) || is_string($userRolesOrUser)) {
            $user = $userRolesOrUser;
        } elseif (is_array($userRolesOrUser)) {
            $user = $this
                ->getMockBuilder('Tenside\Core\Security\UserInformationInterface')
                ->setMethods(['getRoles'])
                ->getMockForAbstractClass();
            $user->method('getRoles')->willReturn($userRolesOrUser);
        } else {
            throw new \InvalidArgumentException(
                'Values passed as $userRolesOrUser is invalid: ' . var_export($userRolesOrUser, true)
            );
        }

        $token = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->setMethods(['getUser'])
            ->getMockForAbstractClass();
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Test that it can be instantiated.
     *
     * @return void
     */
    public function testCreation()
    {
        $voter = new PermissionVoter(
            $this->getMockForAbstractClass(RouterInterface::class),
            $this->getMockForAbstractClass(RequestStack::class)
        );

        $this->assertInstanceOf(PermissionVoter::class, $voter);
    }

    /**
     * Test that it supports the attribute ROLE_CHECK.
     *
     * @return void
     */
    public function testSupportsAttribute()
    {
        $voter = $this->mockVoter();
        $this->assertTrue($voter->supportsAttribute('ROLE_CHECK'));
        $this->assertFalse($voter->supportsAttribute('anything-else'));
    }

    /**
     * Test that it supports any class.
     *
     * @return void
     */
    public function testSupportsAnyClass()
    {
        $voter = $this->mockVoter();
        $this->assertTrue($voter->supportsClass(null));
        $this->assertTrue($voter->supportsClass(new Request()));
    }

    /**
     * Provider for the voting test.
     *
     * @return array
     */
    public function votingTestProvider()
    {
        // ORDER:
        // - comment
        // - expected result.
        // - supported roles/user.
        // - object/request
        // - attributes
        // - required role
        return [
            [
                'Should abstain null request without any attributes',
                PermissionVoter::ACCESS_ABSTAIN,
                null,
                null,
                [],
                null,
            ],
            [
                'Should abstain null request with only unsupported attributes',
                PermissionVoter::ACCESS_ABSTAIN,
                null,
                null,
                ['UNSUPPORTED_ATTRIBUTE'],
                null,
            ],
            [
                'Should abstain request without required_role',
                PermissionVoter::ACCESS_ABSTAIN,
                UserInformation::$roleMap,
                $this->mockRequest(),
                ['ROLE_CHECK'],
                null,
            ],
            [
                'Should abstain request (from stack) without required_role',
                PermissionVoter::ACCESS_ABSTAIN,
                UserInformation::$roleMap,
                new \stdClass(),
                ['ROLE_CHECK'],
                null,
            ],
            [
                'Should deny request when user is only username',
                PermissionVoter::ACCESS_DENIED,
                'username',
                null,
                ['ROLE_CHECK'],
                'ROLE_MANIPULATE_REQUIREMENTS',
            ],
            [
                'Should deny request when user is object',
                PermissionVoter::ACCESS_DENIED,
                new \stdClass(),
                null,
                ['ROLE_CHECK'],
                'ROLE_MANIPULATE_REQUIREMENTS',
            ],
            [
                'Should deny request when user has no roles',
                PermissionVoter::ACCESS_DENIED,
                [],
                null,
                ['ROLE_CHECK'],
                'ROLE_MANIPULATE_REQUIREMENTS',
            ],
            [
                'Should deny request when user has roles but is missing the required_role',
                PermissionVoter::ACCESS_DENIED,
                ['UNKNOWN_ROLE_1', 'UNKNOWN_ROLE_2', 'UNKNOWN_ROLE_3'],
                null,
                ['ROLE_CHECK'],
                'ROLE_MANIPULATE_REQUIREMENTS',
            ],
            [
                'Should grant request when user has required role',
                PermissionVoter::ACCESS_GRANTED,
                ['UNKNOWN_ROLE_1', 'UNKNOWN_ROLE_2', 'ROLE_MANIPULATE_REQUIREMENTS', 'UNKNOWN_ROLE_3'],
                null,
                ['ROLE_CHECK'],
                'ROLE_MANIPULATE_REQUIREMENTS',
            ],
        ];
    }

    /**
     * Test that it abstains when no request is given and no supported attribute.
     *
     * @param string                            $comment         The comment what the test should have done.
     * @param int                               $expectedResult  The expected result.
     * @param array|UserInterface|object|string $userRolesOrUser Anything to be used as user.
     * @param mixed                             $object          The object.
     * @param string[]                          $attributes      The attributes for the object.
     * @param null|string                       $requiredRole    The required role.
     *
     * @return void
     *
     * @dataProvider votingTestProvider
     */
    public function testVoter($comment, $expectedResult, $userRolesOrUser, $object, $attributes, $requiredRole)
    {
        $this->assertEquals(
            $expectedResult,
            $this->mockVoter($requiredRole)->vote($this->mockToken($userRolesOrUser), $object, $attributes),
            $comment
        );
    }
}
