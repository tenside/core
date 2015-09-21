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

namespace Tenside\Test\Task;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Tenside\CoreBundle\Events\CreateTaskEvent;
use Tenside\Task\Task;
use Tenside\Task\TaskList;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the task list.
 */
class TaskListTest extends TestCase
{
    /**
     * Create the event dispatcher with a mocked listener.
     *
     * @return EventDispatcher
     */
    private function getEventDispatcher()
    {
        $unitTest   = $this;
        $dispatcher = new EventDispatcher();
        $listener   = function (CreateTaskEvent $event) use ($unitTest) {
            if ('unknown-type' === $event->getMetaData()->get(Task::SETTING_TYPE)) {
                return;
            }

            $task = $this
                ->getMockBuilder('Tenside\Task\Task')
                ->setConstructorArgs([$event->getMetaData()])
                ->setMethods(['getType', 'perform'])
                ->getMockForAbstractClass();

            $task->method('getType')->willReturn($event->getMetaData()->get(Task::SETTING_TYPE));

            $event->setTask($task);
        };

        $dispatcher->addListener('tenside.create_task', $listener);

        return $dispatcher;
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
        $list = new TaskList($this->workDir, $this->getEventDispatcher());

        $this->assertNull($list->dequeue());
    }

    /**
     * Test that adding a task works.
     *
     * @return void
     */
    public function testAddTask()
    {
        $list = new TaskList($this->workDir, $this->getEventDispatcher());

        $taskId = $list->queue('upgrade');

        $this->assertContains($taskId, $list->getIds());
        $this->assertInstanceOf('Tenside\Task\Task', $list->getTask($taskId));
    }

    /**
     * Test that retrieving an unknown id will return null.
     *
     * @return void
     */
    public function testUnknownIdReturnsNull()
    {
        $list = new TaskList($this->workDir, $this->getEventDispatcher());

        $taskId = $list->queue('upgrade');
        $this->assertNull($list->getTask($taskId . 'some-suffix-to-break-it'));
        $this->assertNull($list->dequeue($taskId . 'some-suffix-to-break-it'));

        // Now ensure the list is unchanged.
        $this->assertContains($taskId, $list->getIds());
        $this->assertInstanceOf('Tenside\Task\Task', $list->getTask($taskId));
    }

    /**
     * Test that adding a task works.
     *
     * @return void
     */
    public function testUnknownTypeReturnsNull()
    {
        $list = new TaskList($this->workDir, $this->getEventDispatcher());

        $taskId = $list->queue('unknown-type');

        $this->assertContains($taskId, $list->getIds());
        $this->assertNull($list->dequeue($taskId));
        $this->assertEmpty($list->getIds());
    }

    /**
     * Test that an task list will return all tasks as they have been added.
     *
     * @return void
     */
    public function testListReturnsAsFifo()
    {
        $list   = new TaskList($this->workDir, $this->getEventDispatcher());
        $first  = $list->queue('upgrade', new JsonArray(['test' => 'value1']));
        $second = $list->queue('upgrade', new JsonArray(['test' => 'value2']));

        $task = $list->dequeue();
        $this->assertInstanceOf('Tenside\Task\Task', $task);
        $this->assertEquals($first, $task->getId());
        $task = $list->dequeue();
        $this->assertInstanceOf('Tenside\Task\Task', $task);
        $this->assertEquals($second, $task->getId());

        $this->assertEmpty($list->getIds());
        $this->assertNull($list->dequeue());
    }
}
