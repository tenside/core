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

use Symfony\Component\Security\Core\Role\Role;
use Tenside\Core\Security\JavascriptWebToken;
use Tenside\Core\Test\TestCase;

/**
 * Test the application.
 */
class JavascriptWebTokenTest extends TestCase
{
    /**
     * Test creation with only the mandatory values.
     *
     * @return void
     */
    public function testCreate()
    {
        $token = new JavascriptWebToken('CREDENTIALS', 'PROVIDER');

        $this->assertEquals('CREDENTIALS', $token->getCredentials());
        $this->assertEquals('PROVIDER', $token->getProviderKey());
        $this->assertEquals('anon.', $token->getUsername());
        $this->assertEmpty($token->getRoles());
        $this->assertFalse($token->isAuthenticated());
        $token->eraseCredentials();
        $this->assertNull($token->getCredentials());
    }

    /**
     * Test creation with an empty provider key raises an exception.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testCreateWithEmptyProviderKeyBails()
    {
        new JavascriptWebToken('CREDENTIALS', '');
    }

    /**
     * Test creation with all values.
     *
     * @return void
     */
    public function testCreateWithRolesIsAuthenticated()
    {
        $token = new JavascriptWebToken('CREDENTIALS', 'PROVIDER', 'username', ['ROLE_1', 'ROLE_2']);

        $this->assertEquals('CREDENTIALS', $token->getCredentials());
        $this->assertEquals('PROVIDER', $token->getProviderKey());
        $this->assertEquals('username', $token->getUsername());
        /** @var Role[] $roles */
        $roles = $token->getRoles();
        $this->assertEquals(2, count($roles));
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertEquals('ROLE_1', $roles[0]->getRole());
        $this->assertInstanceOf(Role::class, $roles[1]);
        $this->assertEquals('ROLE_2', $roles[1]->getRole());
        $this->assertTrue($token->isAuthenticated());
    }

    /**
     * Test serialization and un-serialization.
     *
     * @return void
     */
    public function testSerializationAndUnSerialization()
    {
        $token      = new JavascriptWebToken('CREDENTIALS', 'PROVIDER', 'username', ['ROLE_1', 'ROLE_2']);
        $serialized = $token->serialize();

        $token = new JavascriptWebToken('TMP', 'TMP');
        $token->unserialize($serialized);

        $this->assertEquals('CREDENTIALS', $token->getCredentials());
        $this->assertEquals('PROVIDER', $token->getProviderKey());
        $this->assertEquals('username', $token->getUsername());
        /** @var Role[] $roles */
        $roles = $token->getRoles();
        $this->assertEquals(2, count($roles));
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertEquals('ROLE_1', $roles[0]->getRole());
        $this->assertInstanceOf(Role::class, $roles[1]);
        $this->assertEquals('ROLE_2', $roles[1]->getRole());
        $this->assertTrue($token->isAuthenticated());
    }
}
