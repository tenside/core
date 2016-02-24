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

use Tenside\Core\Task\CompositeTaskFactory;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This tests the composite task factory.
 */
class CompositeTaskFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a mock factory supporting one type.
     *
     * @param string $type The type that the factory shall support.
     *
     * @return TaskFactoryInterface
     */
    private function mockFactory($type)
    {
        $factory = $this->getMockForAbstractClass(TaskFactoryInterface::class);

        $factory->method('isTypeSupported')->willReturnCallback(
            function ($taskType) use ($type) {
                return $taskType === $type;
            }
        );

        $factory->method('createInstance')->willReturnCallback(
            function ($taskType, JsonArray $metaData) use ($type) {
                if ($taskType !== $type) {
                    throw new \InvalidArgumentException('UNSUPPORTED TYPE');
                }
                $task = $this
                    ->getMockBuilder(Task::class)
                    ->setConstructorArgs([$metaData])
                    ->setMethods(['getType', 'perform'])
                    ->getMockForAbstractClass();

                $task->method('getType')->willReturn($metaData->get(Task::SETTING_TYPE));

                return $task;
            }
        );

        return $factory;
    }

    /**
     * Test that the factory tells what it supports.
     */
    public function testSupportsRegistered()
    {
        $factory = new CompositeTaskFactory([
            $this->mockFactory('test-task'),
            $this->mockFactory('test-task2')
        ]);

        $this->assertTrue($factory->isTypeSupported('test-task'));
        $this->assertTrue($factory->isTypeSupported('test-task2'));
        $this->assertFalse($factory->isTypeSupported('test-task3'));
    }

    /**
     * Test that the factory can create what it supports.
     */
    public function testCreatesRegistered()
    {
        $factory = new CompositeTaskFactory([
            $this->mockFactory('test-task'),
            $this->mockFactory('test-task2')
        ]);

        $this->assertInstanceOf(Task::class, $factory->createInstance('test-task', new JsonArray()));
        $this->assertInstanceOf(Task::class, $factory->createInstance('test-task2', new JsonArray()));
    }

    /**
     * Test that the factory throws exceptions when creating things it does not support.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForCreatingUnregistered()
    {
        $factory = new CompositeTaskFactory([
            $this->mockFactory('test-task'),
            $this->mockFactory('test-task2')
        ]);

        $factory->createInstance('test-task3', new JsonArray());
    }
}
