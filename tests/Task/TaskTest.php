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
     */
    public function testAll()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setConstructorArgs([new JsonArray(['id' => 'test-task-id'])])
            ->setMethods(['getType', 'perform'])
            ->getMockForAbstractClass();

        $task->method('getType')->willReturn('test-task');

        /** @var Task $task */

        $this->assertEquals('test-task-id', $task->getId());
        $this->assertEquals('', $task->getOutput());
        $task->addOutput('Foo');
        $this->assertEquals('Foo', $task->getOutput());

        $this->assertInstanceOf('Composer\IO\IOInterface', $task->getIO());
        $task->getIO()->write('Test');
        $this->assertEquals('FooTest' . PHP_EOL, $task->getOutput());

        $this->assertNull($task->getStatus());
        $reflection = new \ReflectionMethod('Tenside\Task\Task', 'setStatus');
        $reflection->setAccessible(true);
        $reflection->invoke($task, 'RUNNING');
        $this->assertEquals('RUNNING', $task->getStatus());
    }
}
