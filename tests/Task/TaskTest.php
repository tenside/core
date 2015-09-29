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

use Tenside\Task\Task;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the abstract class task.
 */
class TaskTest extends TestCase
{
    /**
     * Test that the base functionality works.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testAll()
    {
        $test = $this;
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();

        $task->method('getType')->willReturn('test-task');
        $task->expects($this->once())->method('doPerform')->willReturnCallback(function () use ($test, $task) {
            $this->assertEquals(Task::STATE_RUNNING, $task->getStatus());
        });

        /** @var Task $task */
        $task->perform($this->getTempFile('task.log'));

        $this->assertEquals('test-task-id', $task->getId());
        $task->addOutput('Foo');
        $this->assertEquals('Foo', substr($task->getOutput(), -3));

        $this->assertInstanceOf('Composer\IO\IOInterface', $task->getIO());
        $task->getIO()->write('Test');
        $this->assertEquals('FooTest' . PHP_EOL, substr($task->getOutput(), -8));
        $skip = strlen($task->getOutput());
        $this->assertEquals('Test' . PHP_EOL, $task->getOutput($skip - 5));

        $this->assertEquals(Task::STATE_FINISHED, $task->getStatus());
    }

    /**
     * Test that running a task twice raises an exception.
     *
     * @return void
     *
     * @expectedException \LogicException
     */
    public function testRunningTwiceRaisesException()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('doPerform');

        /** @var Task $task */
        $logfile = $this->getTempFile('task.log');
        $task->perform($logfile);
        $task->perform($logfile);
    }

    /**
     * Test that retrieving the output of a not yet started task raises an exception.
     *
     * @return void
     *
     * @expectedException \LogicException
     */
    public function testGetOutputFromNotStartedTaskRaisesException()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();

        $task->getOutput();
    }

    /**
     * Test that retrieving the output of a not yet started task raises an exception.
     *
     * @return void
     *
     * @expectedException \LogicException
     */
    public function testAddOutputToNotStartedTaskRaisesException()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();

        $task->addOutput('Will not be executed');
    }

    /**
     * Test that any exception during execution will get added to the task log.
     *
     * @return void
     */
    public function testErrorInExecutionWillAddErrorOutput()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('doPerform')->willReturnCallback(function () {
            throw new \RuntimeException('Fail miserably!');
        });

        /** @var Task $task */

        $exception = null;
        try {
            $task->perform($this->getTempFile('task.log'));
        } catch (\RuntimeException $exception) {
            // Just to keep the exception.
        }

        $this->assertEquals(Task::STATE_ERROR, $task->getStatus());
        $this->assertContains('Fail miserably!', $exception->getMessage());
        $this->assertContains('Fail miserably!', $task->getOutput());
    }

    /**
     * Test that the task will use the log file specified in the config.
     *
     * @return void
     */
    public function testTaskWillUseLogFromConfig()
    {
        $log  = $this->getTempFile('testlog');
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id', 'log' => $log])])
            ->setMethods(['getType', 'doPerform'])
            ->getMockForAbstractClass();
        /** @var Task $task */

        $task->addOutput('Test output');

        $this->assertEquals('Test output', file_get_contents($log));
    }
}
