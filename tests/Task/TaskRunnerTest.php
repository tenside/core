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

use Tenside\Task\Runner;
use Tenside\Task\Task;
use Tenside\Task\TaskOutput;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

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
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setMethods(['perform'])
            ->setConstructorArgs([new JsonArray()])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('perform')->willReturn(Task::STATE_FINISHED);

        $runner = new Runner($task);

        $this->assertTrue($runner->run($this->getTempFile()));
    }

    /**
     * Test that writing works.
     *
     * @return void
     */
    public function testReturnsFalseOnError()
    {
        $task = $this
            ->getMockBuilder('Tenside\Task\Task')
            ->setMethods(['perform'])
            ->setConstructorArgs([new JsonArray()])
            ->getMockForAbstractClass();
        $task->expects($this->once())->method('perform')->willReturn(Task::STATE_ERROR);

        $runner = new Runner($task);

        $this->assertFalse($runner->run($this->getTempFile()));
    }
}
