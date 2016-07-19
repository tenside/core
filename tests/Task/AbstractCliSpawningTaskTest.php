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

use Symfony\Component\Process\Process;
use Tenside\Core\Task\AbstractCliSpawningTask;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the AbstractCliSpawningTask class
 */
class AbstractCliSpawningTaskTest extends TestCase
{
    /**
     * Test that a process gets correctly run.
     *
     * @return void
     */
    public function testRunProcess()
    {
        $json = new JsonArray(['log' => $this->getTempFile('task.log')]);

        $process = $this->getMock(Process::class, [], ['/bin/test']);
        $process->method('run')->willReturnCallback(function ($callback) {
            $callback(Process::ERR, 'stderr');
            $callback(Process::OUT, 'stdout');
        });
        $process->method('getExitCode')->willReturn(0);

        $task = $this->getMockBuilder(AbstractCliSpawningTask::class)
            ->setConstructorArgs([$json])
            ->getMockForAbstractClass();
        /** @var AbstractCliSpawningTask $task */

        $runProcess = new \ReflectionMethod($task, 'runProcess');
        $runProcess->setAccessible(true);
        $runProcess->invoke($task, $process);

        $this->assertEquals('stderrstdout', $task->getOutput());
    }

    /**
     * Test that a failed process causes an exception.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function testFailedExecutionRaisesException()
    {
        $task = $this->getMockBuilder(AbstractCliSpawningTask::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        /** @var AbstractCliSpawningTask $task */
        $process = $this->getMockBuilder(Process::class)->disableOriginalConstructor()->getMock();
        $process->method('getExitCode')->willReturn(1);
        $runProcess = new \ReflectionMethod($task, 'runProcess');
        $runProcess->setAccessible(true);
        $runProcess->invoke($task, $process);
    }
}
