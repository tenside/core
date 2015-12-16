<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests;

use Tenside\CoreBundle\TensideJsonConfig;
use Tenside\CoreBundle\UriSigner;

class UriSignerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock a config containing the secret.
     *
     * @param string $secret The secret to use.
     *
     * @return TensideJsonConfig
     */
    private function mockConfig($secret)
    {
        $mock = $this
            ->getMockBuilder('Tenside\\CoreBundle\\TensideJsonConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('getSecret')->willReturn($secret);

        return $mock;
    }

    /**
     * Test signing.
     *
     * @return void
     */
    public function testSign()
    {
        $signer = new UriSigner($this->mockConfig('foobar'));

        $this->assertContains('?_hash=', $signer->sign('http://example.com/foo'));
        $this->assertContains('&_hash=', $signer->sign('http://example.com/foo?foo=bar'));
    }

    /**
     * Test checking of the signed url.
     *
     * @return void
     */
    public function testCheck()
    {
        $signer = new UriSigner($this->mockConfig('foobar'));

        $this->assertFalse($signer->check('http://example.com/foo?_hash=foo'));
        $this->assertFalse($signer->check('http://example.com/foo?foo=bar&_hash=foo'));
        $this->assertFalse($signer->check('http://example.com/foo?foo=bar&_hash=foo&bar=foo'));

        $this->assertTrue($signer->check($signer->sign('http://example.com/foo')));
        $this->assertTrue($signer->check($signer->sign('http://example.com/foo?foo=bar')));

        $this->assertEquals(
            $signer->sign('http://example.com/foo?foo=bar&bar=foo'),
            $signer->sign('http://example.com/foo?bar=foo&foo=bar')
        );
    }

    /**
     * Test that it also works with argument separator "&amp;".
     *
     * @return void
     */
    public function testCheckWithDifferentArgSeparator()
    {
        $this->iniSet('arg_separator.output', '&amp;');
        $signer = new UriSigner($this->mockConfig('foobar'));

        $this->assertSame(
            'http://example.com/foo?baz=bay&foo=bar&_hash=rIOcC%2FF3DoEGo%2FvnESjSp7uU9zA9S%2F%2BOLhxgMexoPUM%3D',
            $signer->sign('http://example.com/foo?foo=bar&baz=bay')
        );
        $this->assertTrue($signer->check($signer->sign('http://example.com/foo?foo=bar&baz=bay')));
    }
}
