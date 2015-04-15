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
        $this->assertFalse($json->has('foo\/bar'));
        $this->assertTrue($json->has('foo/bar'));
        $this->assertFalse($json->has('foo/bar/baz'));
        $this->assertEquals('baz', $json->get('foo/bar'));
        $this->assertNull($json->get('foo\/bar'));
    }
}
