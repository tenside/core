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

use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Task\TaskList;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the task list.
 */
class TaskListTest extends TestCase
{
    /**
     * Create the event dispatcher with a mocked listener.
     *
     * @return TaskFactoryInterface
     */
    private function getFactory()
    {
        $factory = $this
            ->getMockBuilder(TaskFactoryInterface::class)
            ->getMockForAbstractClass();

        $factory->method('isTypeSupported')->willReturnCallback(
            function ($taskType) {
                return $taskType !== 'unknown-type';
            }
        );

        $factory->method('createInstance')->willReturnCallback(
            function ($taskType, JsonArray $metaData) {
                if ($taskType === 'unknown-type') {
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
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workDir = $this->getTempDir();
    }

    /**
     * Test that an empty task list will not return a task when dequeuing.
     *
     * @return void
     */
    public function testEmptyListDoesNotReturnTask()
    {
        $list = new TaskList($this->workDir, $this->getFactory());

        $this->assertNull($list->dequeue());
    }

    /**
     * Test that adding a task works.
     *
     * @return void
     */
    public function testAddTask()
    {
        $list = new TaskList($this->workDir, $this->getFactory());

        $taskId = $list->queue('upgrade');

        $this->assertContains($taskId, $list->getIds());
        $this->assertInstanceOf(Task::class, $list->getTask($taskId));
        $this->assertInstanceOf(Task::class, $list->getNext());
    }

    /**
     * Test that retrieving an unknown id will return null.
     *
     * @return void
     */
    public function testUnknownIdReturnsNull()
    {
        $list = new TaskList($this->workDir, $this->getFactory());

        $taskId = $list->queue('upgrade');
        $this->assertNull($list->getTask($taskId . 'some-suffix-to-break-it'));
        $this->assertNull($list->dequeue($taskId . 'some-suffix-to-break-it'));

        // Now ensure the list is unchanged.
        $this->assertContains($taskId, $list->getIds());
        $this->assertInstanceOf(Task::class, $list->getTask($taskId));
    }

    /**
     * Test that adding a task works.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testUnknownTypeRaisesException()
    {
        $list = new TaskList($this->workDir, $this->getFactory());

        $list->queue('unknown-type');
    }

    /**
     * Test that an task list will return all tasks as they have been added.
     *
     * @return void
     */
    public function testListReturnsAsFifo()
    {
        $list   = new TaskList($this->workDir, $this->getFactory());
        $first  = $list->queue('upgrade', new JsonArray(['test' => 'value1']));
        $second = $list->queue('upgrade', new JsonArray(['test' => 'value2']));

        $task = $list->dequeue();
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($first, $task->getId());
        $task = $list->dequeue();
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($second, $task->getId());

        $this->assertEmpty($list->getIds());
        $this->assertNull($list->dequeue());
    }

    /**
     * Test that an task list will return all tasks as they have been added but not remove them.
     *
     * @return void
     */
    public function testListGetNextReturnsCorrectTasksAndRemoveRemovesThem()
    {
        $list   = new TaskList($this->workDir, $this->getFactory());
        $first  = $list->queue('upgrade', new JsonArray(['test' => 'value1']));
        $second = $list->queue('upgrade', new JsonArray(['test' => 'value2']));

        $task = $list->getNext();
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($first, $task->getId());
        $task = $list->getNext();
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($first, $task->getId());
        $list->remove($first);

        $task = $list->getNext();
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($second, $task->getId());

        $list->remove($second);
        $this->assertNull($list->getNext());
    }
}
