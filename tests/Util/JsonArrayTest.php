<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Test\Util;

use Tenside\Util\JsonArray;

/**
 * Test the JsonArray handler.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class JsonArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that an empty array does not return values.
     *
     * @return void
     */
    public function testEmpty()
    {
        $json = new JsonArray();

        $this->assertEquals(null, $json->get(''));
        $this->assertEquals(null, $json->get('foo'));
        $this->assertFalse($json->has('foo'));
    }

    /**
     * Test that values are found by path.
     *
     * @return void
     */
    public function testPath()
    {
        $json = new JsonArray(['foo' => ['bar' => 'baz']]);
        $json->set('bar/bar\/baz', 'foo');

        $this->assertFalse($json->has('foo\/bar'));
        $this->assertTrue($json->has('foo/bar'));
        $this->assertFalse($json->has('foo/bar/baz'));
        $this->assertEquals('baz', $json->get('foo/bar'));
        $this->assertNull($json->get('foo\/bar'));
        $this->assertTrue($json->has('bar/bar\/baz'));
        $this->assertEquals('foo', $json->get('bar/bar\/baz'));
        $this->assertEquals(['bar/baz' => 'foo'], $json->get('bar'));
    }

    /**
     * Test that array values below a path are absorbed.
     *
     * @return void
     */
    public function testPathSubArray()
    {
        $json = new JsonArray(['top1' => ['sub1.1' => 'top1.sub1.1.content']]);

        $json->set('top2', ['sub2.1' => []]);
        $json->set('top2/sub2.2', ['top2.sub2.2.content']);
        $this->assertEquals(['sub2.1' => [], 'sub2.2' => ['top2.sub2.2.content']], $json->get('top2'));
        $this->assertEquals([], $json->get('top2/sub2.1'));
        $this->assertEquals(['top2.sub2.2.content'], $json->get('top2/sub2.2'));
        $json->set('top2', 'top2.content');
        $this->assertEquals('top2.content', $json->get('top2'));
    }
}
