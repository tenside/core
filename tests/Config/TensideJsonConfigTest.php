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

namespace Tenside\Core\Test\Config;

use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the tenside json handling.
 */
class TensideJsonConfigTest extends TestCase
{
    /**
     * Test provider.
     *
     * @return array
     */
    public function providerTestDelegation()
    {
        return [
            [
                'setSecret',
                'getSecret',
                'secret',
                '$ecret',
                null,
            ],
            [
                'setLocalDomain',
                'getLocalDomain',
                'domain',
                'example.org',
                null,
            ],
            [
                'setPhpCliBinary',
                'getPhpCliBinary',
                'php_cli',
                '/usr/bin/php',
                'php',
            ],
            [
                'setPhpCliArguments',
                'getPhpCliArguments',
                'php_cli_arguments',
                ['-dFOO=bar'],
                null,
            ],
            [
                'setPhpCliEnvironment',
                'getPhpCliEnvironment',
                'php_cli_environment',
                ['HOME=/xyz', 'FOO=bar'],
                null,
            ],
            [
                'setForkingAvailable',
                'isForkingAvailable',
                'php_can_fork',
                true,
                false,
            ],
        ];
    }

    /**
     * Test that the deletion works.
     *
     * @param string $setter    The setter to call.
     *
     * @param string $getter    The getter to call.
     *
     * @param string $configKey The config key in the json array that will get populated.
     *
     * @param mixed  $value     The value to pass.
     *
     * @param mixed  $default   The default value that shall be returned if nothing has been set.
     *
     * @return void
     *
     * @dataProvider providerTestDelegation
     */
    public function testDelegation($setter, $getter, $configKey, $value, $default)
    {
        $array  = new JsonArray();
        $config = new TensideJsonConfig($array);

        $this->assertEquals($default, call_user_func([$config, $getter]));
        $this->assertSame($config, call_user_func([$config, $setter], $value));
        $this->assertEquals($value, $array->get($configKey));
        $this->assertEquals($value, call_user_func([$config, $getter]));
    }

    /**
     * Test that adding arguments works.
     *
     * @return void
     */
    public function testAddCommandLineArgument()
    {
        $config = new TensideJsonConfig(new JsonArray());
        $this->assertEquals(null, $config->getPhpCliArguments());
        $this->assertSame($config, $config->addCommandLineArgument('test1'));
        $this->assertEquals(['test1'], $config->getPhpCliArguments());
        $this->assertSame($config, $config->addCommandLineArgument('test2'));
        $this->assertEquals(['test1', 'test2'], $config->getPhpCliArguments());
    }
}
