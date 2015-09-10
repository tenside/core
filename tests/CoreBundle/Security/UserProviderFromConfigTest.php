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

use Tenside\Config\SourceJson;
use Tenside\CoreBundle\Security\UserInformation;
use Tenside\CoreBundle\Security\UserProviderFromConfig;
use Tenside\Test\TestCase;

/**
 * Test the application.
 */
class UserProviderFromConfigTest extends TestCase
{
    /**
     * Test the supportsClass() method.
     *
     * @return void
     */
    public function testSupportsClass()
    {
        $provider = new UserProviderFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        $this->assertFalse($provider->supportsClass('UnknownClass'));
        $this->assertTrue($provider->supportsClass('Tenside\\CoreBundle\\Security\\UserInformation'));
    }

    /**
     * Test that an empty config does not load any users.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testUnknownUserThrowsException()
    {
        $provider = new UserProviderFromConfig(new SourceJson($this->getTempFile('tenside.json')));

        $provider->loadUserByUsername('unknown-username');
    }

    /**
     * Test the whole functionality.
     *
     * @return void
     */
    public function testFunctionality()
    {
        $config   = new SourceJson($this->getTempFile('tenside.json'));
        $provider = new UserProviderFromConfig($config);
        $provider->addUser(new UserInformation(['username' => 'tester', 'foo' => 'bar']));
        $this->assertTrue($config->has('auth-password/tester'));

        $refreshed = $provider->refreshUser(new UserInformation(['username' => 'tester']));
        $this->assertInstanceOf('Tenside\\CoreBundle\\Security\\UserInformationInterface', $refreshed);
        $this->assertEquals('tester', $refreshed->getUsername());
        $this->assertEquals('bar', $refreshed->get('foo'));

        $provider->removeUser($refreshed);
        $this->assertFalse($config->has('auth-password/tester'));
    }
}
