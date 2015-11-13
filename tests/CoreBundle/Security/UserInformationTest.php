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

namespace Tenside\Test\CoreBundle\Security;

use Tenside\CoreBundle\Security\UserInformation;

/**
 * Test the application.
 */
class UserInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the has access level method.
     *
     * @return void
     */
    public function testHasAccessLevel()
    {
        $user = new UserInformation(['acl' => UserInformation::ROLE_ALL]);

        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_ALL));
        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_UPGRADE));
        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_MANIPULATE_REQUIREMENTS));
        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_EDIT_COMPOSER_JSON));
        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_EDIT_APPKERNEL));

        $this->assertEquals(
            ['ROLE_UPGRADE',
             'ROLE_MANIPULATE_REQUIREMENTS',
             'ROLE_EDIT_COMPOSER_JSON',
             'ROLE_EDIT_APP_KERNEL',
            ],
            $user->getRoles()
        );
    }

    /**
     * Test the empty user has no access levels granted.
     *
     * @return void
     */
    public function testHasNotAccessLevel()
    {
        $user = new UserInformation();

        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_ALL));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_UPGRADE));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_MANIPULATE_REQUIREMENTS));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_EDIT_COMPOSER_JSON));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_EDIT_APPKERNEL));
        $this->assertEquals([], $user->getRoles());
    }

    /**
     * Test setting of access levels works.
     *
     * @return void
     */
    public function testSetAccessLevel()
    {
        $user = new UserInformation();
        $user->setAccessLevel(UserInformation::ROLE_MANIPULATE_REQUIREMENTS);
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_ALL));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_UPGRADE));
        $this->assertTrue($user->hasAccessLevel(UserInformation::ROLE_MANIPULATE_REQUIREMENTS));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_EDIT_COMPOSER_JSON));
        $this->assertFalse($user->hasAccessLevel(UserInformation::ROLE_EDIT_APPKERNEL));
        $this->assertEquals(['ROLE_MANIPULATE_REQUIREMENTS'], $user->getRoles());
    }

    /**
     * Test setting of values and retrieval.
     *
     * @return void
     */
    public function testValueSetterAndGetter()
    {
        $user = new UserInformation(['key1' => 'value1']);
        $this->assertEquals(['acl', 'key1'], $user->keys());
        $this->assertEquals('value1', $user->get('key1'));
        $this->assertFalse($user->has('key2'));
        $this->assertEquals(null, $user->get('key2'));
        $this->assertEquals('default', $user->get('key2', 'default'));
        $this->assertSame($user, $user->set('key2', 'value2'));
        $this->assertEquals('value2', $user->get('key2'));

        $keys = array_flip($user->keys());
        $this->assertEquals(['acl', 'key1', 'key2'], array_keys($keys));
        foreach ($user as $key => $value) {
            $this->assertArrayHasKey($key, $keys);
            $this->assertEquals($user->get($key), $value);
        }

        $this->assertSame($user, $user->remove('key1'));
        $this->assertEquals(null, $user->get('key1'));

        $this->assertInternalType('string', $user->asString());
    }

    /**
     * Test the getSalt() method.
     *
     * @return void
     */
    public function testEmptyUserHasSalt()
    {
        $user = new UserInformation();
        $this->assertNotNull($user->getSalt());
    }

    /**
     * Test the getPassword() method.
     *
     * @return void
     */
    public function testGetPassword()
    {
        $user = new UserInformation(['password' => 'secure-hash']);
        $this->assertEquals('secure-hash', $user->getPassword());
    }

    /**
     * Test the getPassword() method.
     *
     * @return void
     */
    public function testSetPassword()
    {
        $user = new UserInformation(['password' => 'secure-hash']);
        $this->assertSame($user, $user->setPassword('new-secure-hash'));
        $this->assertEquals('new-secure-hash', $user->getPassword());
    }

    /**
     * Test the eraseCredentials() method.
     *
     * @return void
     */
    public function testEraseCredentials()
    {
        $user = new UserInformation(['password' => 'secure-hash']);
        $user->eraseCredentials();

        $this->assertNull($user->getPassword());
        $this->assertNull($user->getSalt());
    }
}
