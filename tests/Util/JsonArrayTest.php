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

namespace Tenside\Test\Util;

use Tenside\Util\JsonArray;

/**
 * Test the JsonArray handler.
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

        $this->assertEquals([], $json->get('/'));
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

    /**
     * Provider method for testGet
     *
     * @return array
     */
    public function getProvider()
    {
        return [
            [
                [],
                [],
                '/'
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                '/'
            ],
            [
                'value1',
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                '/key1'
            ],
        ];
    }

    /**
     * Test the get method.
     *
     * @param mixed  $expected The expected result.
     * @param array  $array    The array to check.
     * @param string $path     The path to retrieve.
     *
     * @return void
     *
     * @dataProvider getProvider
     */
    public function testGet($expected, $array, $path)
    {
        $json = new JsonArray($array);

        $this->assertEquals($expected, $json->get($path));
    }

    /**
     * Test the get method with forceArray flag.
     *
     * @return void
     */
    public function testGetForceArray()
    {
        $json = new JsonArray(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']);

        $this->assertEquals(['value1'], $json->get('/key1', true));
        $this->assertEquals([], $json->get('/does-not-exist', true));
    }

    /**
     * Provider method for testGet
     *
     * @return array
     */
    public function setProvider()
    {
        return [
            [
                ['key1' => 'value1'],
                [],
                'key1',
                'value1'
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value33'],
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                '/key3',
                'value33'
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                [],
                '/',
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2'],
                ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'],
                '/key3',
                null,
            ],
            [
                ['key1' => 'value1', 'key2' => 'value2', 'foo' => 'test'],
                ['key1' => 'value1', 'key2' => 'value2'],
                '//foo',
                'test',
            ],
        ];
    }

    /**
     * Test the set method.
     *
     * @param mixed  $expected The expected result array.
     * @param array  $array    The array to work on.
     * @param string $path     The path to set.
     * @param mixed  $value    The value to set.
     *
     * @return void
     *
     * @dataProvider setProvider
     */
    public function testSet($expected, $array, $path, $value)
    {
        $json = new JsonArray($array);

        $this->assertEquals($json, $json->set($path, $value));
        $this->assertEquals($expected, $json->getData());
    }

    /**
     * Test the remove method.
     *
     * @return void
     */
    public function testRemove()
    {
        $json = new JsonArray(['test' => ['key1' => 'value1', 'key2' => 'value2', 'bar/baz' => 'foo']]);

        $this->assertEquals($json, $json->remove('/test/key2'));
        $this->assertEquals(['test' => ['key1' => 'value1', 'bar/baz' => 'foo']], $json->getData());
    }

    /**
     * Test the isEmpty method.
     *
     * @return void
     */
    public function testIsEmpty()
    {
        $json = new JsonArray(['test' => ['key1' => '', 'key2' => 'false']]);

        $this->assertTrue($json->isEmpty('key1'));
        $this->assertTrue($json->isEmpty('key2'));
        $this->assertTrue($json->isEmpty('key3'));
    }

    /**
     * Test the get entries method.
     *
     * @return void
     */
    public function testGetEntries()
    {
        $json = new JsonArray(['test' => ['key1' => 'value1', 'key2' => 'value2', 'bar/baz' => 'foo']]);

        $this->assertEquals(['test'], $json->getEntries('/'));
        $this->assertEquals(['test/key1', 'test/key2', 'test/bar\/baz'], $json->getEntries('/test'));
    }

    /**
     * Test the merge method.
     *
     * @return void
     */
    public function testMerge()
    {
        $json = new JsonArray(['test' => ['key1' => 'value1', 'key2' => 'value2']]);
        $json->merge(['test' => ['key1' => 'value1', 'key2' => 'foobar', 'key3' => 'three']]);

        $this->assertEquals(['test'], $json->getEntries('/'));
        $this->assertEquals(['test/key1', 'test/key2', 'test/key3'], $json->getEntries('/test'));

        $this->assertEquals('foobar', $json->get('/test/key2'));
    }

    /**
     * Test that an exception is thrown for invalid json data.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testBailsWithBrokenJson()
    {
        new JsonArray('{"foo": "bar",}');
    }

    /**
     * Provide invalid paths.
     *
     * @return array
     */
    public function invalidPathProvider()
    {
        return [
            [''],
            ['//'],
            ['/foo//'],
            ['/foo//bar'],
        ];
    }

    /**
     * Test that an exception is thrown for invalid paths.
     *
     * @param string $path The path.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     *
     * @dataProvider invalidPathProvider
     */
    public function testBailsWithInvalidPaths($path)
    {
        $json = new JsonArray();

        $reflection = new \ReflectionMethod($json, 'splitPath');
        $reflection->setAccessible(true);

        $reflection->invoke($json, $path);
    }
}
