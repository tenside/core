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

namespace Tenside\Core\Test\Task;

use Tenside\Core\Task\AbstractTaskFactory;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the AbstractTaskFactory.
 */
class AbstractTaskFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Ensure that the factory supports tasks when a named method is defined.
     *
     * @return void
     */
    public function testDoesSupport()
    {
        $factory = $this
            ->getMockBuilder(AbstractTaskFactory::class)
            ->setMethods(['createSomeTaskType'])
            ->getMockForAbstractClass();
        /** @var TaskFactoryInterface $factory */
        $this->assertTrue($factory->isTypeSupported('some-task-type'));
    }

    /**
     * Ensure that the factory does not support tasks when no named method is defined.
     *
     * @return void
     */
    public function testDoesNotSupport()
    {
        $factory = $this
            ->getMockBuilder(AbstractTaskFactory::class)
            ->setMethods([])
            ->getMockForAbstractClass();
        /** @var TaskFactoryInterface $factory */
        $this->assertFalse($factory->isTypeSupported('some-other-task-type'));
    }

    /**
     * Test that the factory can create what it supports.
     *
     * @return void
     */
    public function testCreatesRegistered()
    {
        $factory = $this
            ->getMockBuilder(AbstractTaskFactory::class)
            ->setMethods(['createSomeTaskType'])
            ->getMockForAbstractClass();

        $task = $this
            ->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType', 'perform'])
            ->getMockForAbstractClass();
        $task->method('getType')->willReturn('some-task-type');

        $factory->method('createSomeTaskType')->willReturn($task);

        /** @var TaskFactoryInterface $factory */

        $this->assertSame($task, $factory->createInstance('some-task-type', new JsonArray()));
    }

    /**
     * Test that the factory throws exceptions when creating things it does not support.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForCreatingUnregistered()
    {
        $factory = $this
            ->getMockBuilder(AbstractTaskFactory::class)
            ->setMethods(['createSomeTaskType'])
            ->getMockForAbstractClass();

        /** @var TaskFactoryInterface $factory */

        $factory->createInstance('test-task', new JsonArray());
    }
}
