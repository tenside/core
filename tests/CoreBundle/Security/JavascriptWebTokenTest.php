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

use Tenside\CoreBundle\Security\JavascriptWebToken;
use Tenside\Test\TestCase;

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
}
