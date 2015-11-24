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

namespace Tenside\Test\CoreBundle\Controller;

use Symfony\Component\DependencyInjection\Container;
use Tenside\CoreBundle\Controller\AbstractController;

/**
 * Test the abstract controller.
 */
class AbstractControllerTest extends TestCase
{
    /**
     * Test the getInputOutput() method.
     *
     * @return void
     */
    public function testGetInputOutput()
    {
        /** @var AbstractController $controller */
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $instance = $controller->getInputOutput();
        $this->assertInstanceOf('Composer\IO\IOInterface', $instance);
        $this->assertSame($instance, $controller->getInputOutput());
    }

    /**
     * Test the getOutput() method.
     *
     * @return void
     */
    public function testGetOutput()
    {
        /** @var AbstractController $controller */
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $instance = $controller->getInputOutput();
        $instance->write('MOOH!', false);

        $reflection = new \ReflectionMethod($controller, 'getOutput');
        $reflection->setAccessible(true);

        $value = $reflection->invoke($controller);
        $this->assertEquals('MOOH!', $value);
        $this->assertSame($value, $reflection->invoke($controller));
    }

    /**
     * Test the getOutput() method returns null when no output handler has been set yet.
     *
     * @return void
     */
    public function testGetOutputWithoutOutputInstance()
    {
        /** @var AbstractController $controller */
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $reflection = new \ReflectionMethod($controller, 'getOutput');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->invoke($controller));
    }

    /**
     * Test the getTensideConfig() method.
     *
     * @return void
     */
    public function testGetTensideConfig()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $config = $this
            ->getMockBuilder('Tenside\CoreBundle\TensideJsonConfig')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $container = new Container();
        $container->set('tenside.config', $config);
        /** @var AbstractController $controller */
        $controller->setContainer($container);

        $this->assertEquals($config, $controller->getTensideConfig());
    }

    /**
     * Test the getTensideHome() method.
     *
     * @return void
     */
    public function testGetTensideHome()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $container = new Container();
        $container->set('tenside.home', $home);

        /** @var AbstractController $controller */
        $controller->setContainer($container);

        $this->assertEquals($this->getTempDir(), $controller->getTensideHome());
    }

    /**
     * Test the getTensideTasks() method.
     *
     * @return void
     */
    public function testGetTensideTasks()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $tasks = $this
            ->getMockBuilder('Tenside\\Task\\TaskList')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $container = new Container();
        $container->set('tenside.tasks', $tasks);

        /** @var AbstractController $controller */
        $controller->setContainer($container);

        $this->assertEquals($tasks, $controller->getTensideTasks());
    }

    /**
     * Test the getComposer() method.
     *
     * @return void
     */
    public function testGetComposer()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();

        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $this->createFixture('composer.json', '{}');

        $container = new Container();
        $container->set('tenside.home', $home);

        /** @var AbstractController $controller */
        $controller->setContainer($container);

        $this->assertInstanceOf('Composer\Composer', $controller->getComposer());
    }
}
