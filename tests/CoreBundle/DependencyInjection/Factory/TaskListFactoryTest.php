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

namespace Tenside\Test\CoreBundle\DependencyInjection\Factory;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Tenside\CoreBundle\DependencyInjection\Factory\TaskListFactory;
use Tenside\Test\TestCase;

/**
 * Test the task list factory.
 */
class TaskListFactoryTest extends TestCase
{
    /**
     * Test that the factory creates a new instance.
     *
     * @return void
     */
    public function testCreate()
    {
        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $list = TaskListFactory::create($home, $eventDispatcher = new EventDispatcher());

        $this->assertInstanceOf('Tenside\Task\TaskList', $list);
        $dataDir = new \ReflectionProperty($list, 'dataDir');
        $dataDir->setAccessible(true);
        $dispatcher = new \ReflectionProperty($list, 'dispatcher');
        $dispatcher->setAccessible(true);

        $this->assertEquals($home->tensideDataDir(), $dataDir->getValue($list));
        $this->assertEquals($eventDispatcher, $dispatcher->getValue($list));
    }
}
