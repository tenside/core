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

use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\LockHandler;
use Tenside\Core\Task\Runner;
use Tenside\Core\Task\Task;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the task runner.
 */
class TaskRunnerTest extends TestCase
{
    /**
     * Test that writing works.
     *
     * @return void
     */
    public function testReturnsTrueOnSuccess()
    {
        $lock = $this
            ->getMockBuilder(LockHandler::class)
            ->setMethods(['lock', 'release'])
            ->disableOriginalConstructor()
            ->getMock();
        $lock->expects($this->once())->method('lock')->willReturn(true);
        $lock->expects($this->once())->method('release');

        $task = $this
            ->getMockBuilder(Task::class)
            ->setMethods(['perform', 'getStatus'])
            ->setConstructorArgs([new JsonArray([])])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('perform');
        $task->expects($this->once())->method('getStatus')->willReturn(Task::STATE_FINISHED);

        $runner = new Runner($task, $lock, new NullLogger());

        $this->assertTrue($runner->run($this->getTempFile()));
    }

    /**
     * Test that writing works.
     *
     * @return void
     */
    public function testReturnsFalseOnError()
    {
        $lock = $this
            ->getMockBuilder(LockHandler::class)
            ->setMethods(['lock', 'release'])
            ->disableOriginalConstructor()
            ->getMock();
        $lock->expects($this->once())->method('lock')->willReturn(true);
        $lock->expects($this->once())->method('release');

        $task = $this
            ->getMockBuilder(Task::class)
            ->setMethods(['perform'])
            ->setConstructorArgs([new JsonArray()])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('perform')->willReturn(Task::STATE_ERROR);

        $runner = new Runner($task, $lock, new NullLogger());

        $this->assertFalse($runner->run($this->getTempFile()));
    }
}
