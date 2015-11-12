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

use Symfony\Component\Console\Input\ArrayInput;
use Tenside\Task\AbstractComposerCommandTask;
use Tenside\Task\Task;
use Tenside\Test\Task\WrappedCommand\WrappedTestCommand;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the abstract composer command task.
 */
class AbstractComposerCommandTaskTest extends TestCase
{
    /**
     * This tests that normal execution works.
     *
     * @return void
     */
    public function testNormalExecution()
    {
        $task = $this
            ->getMockForAbstractClass(
                'Tenside\\Task\\AbstractComposerCommandTask',
                [new JsonArray(['status' => Task::STATE_PENDING])]
            );

        $command = $this
            ->getMockBuilder('Composer\\Command\\Command')
            ->setConstructorArgs(['testcommand'])
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $command->method('execute')->willReturn(0);

        $task->method('prepareCommand')->willReturn($command);
        $task->method('prepareInput')->willReturn(new ArrayInput([]));

        /** @var AbstractComposerCommandTask $task */
        $task->perform($this->getTempFile('task.log'));

        $this->assertTrue($command->getDefinition()->hasOption('verbose'));
    }

    /**
     * Test that an non zero exit code raises an exception.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testNonZeroExitCodeThrowsException()
    {
        $task = $this
            ->getMockForAbstractClass(
                'Tenside\\Task\\AbstractComposerCommandTask',
                [new JsonArray(['status' => Task::STATE_PENDING])]
            );

        $command = $this
            ->getMockBuilder('Composer\\Command\\Command')
            ->setConstructorArgs(['testcommand'])
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $command->method('execute')->willReturn(1);

        $task->method('prepareCommand')->willReturn($command);
        $task->method('prepareInput')->willReturn(new ArrayInput([]));

        /** @var AbstractComposerCommandTask $task */
        $task->perform($this->getTempFile('task.log'));
    }

    /**
     * Test that an non zero exit code raises an exception.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testExceptionThrowsException()
    {
        $task = $this
            ->getMockForAbstractClass(
                'Tenside\\Task\\AbstractComposerCommandTask',
                [new JsonArray(['status' => Task::STATE_PENDING])]
            );

        $command = $this
            ->getMockBuilder('Composer\\Command\\Command')
            ->setConstructorArgs(['testcommand'])
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $command->method('execute')->willReturnCallback(function () {
            throw new \Exception('This should get converted to RuntimeException');
        });

        $task->method('prepareCommand')->willReturn($command);
        $task->method('prepareInput')->willReturn(new ArrayInput([]));

        /** @var AbstractComposerCommandTask $task */
        $task->perform($this->getTempFile('task.log'));
    }

    /**
     * Test setting the composer factory on valid commands works.
     *
     * @return void
     */
    public function testSetComposerFactoryOnValidCommandWorks()
    {
        $task = $this
            ->getMockForAbstractClass(
                'Tenside\\Task\\AbstractComposerCommandTask',
                [new JsonArray(['status' => Task::STATE_PENDING])]
            );

        $command    = new WrappedTestCommand('testcommand');
        $reflection = new \ReflectionMethod($task, 'attachComposerFactory');
        $reflection->setAccessible(true);
        $reflection->invoke($task, $command);

        $reflection = new \ReflectionMethod($command, 'getComposer');
        $this->assertInstanceOf('Composer\\Composer', $reflection->invoke($command, true));
    }

    /**
     * Test setting the composer factory on invalid commands raises an exception.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSetComposerFactoryOnInvalidCommandThrowsException()
    {
        $task = $this
            ->getMockForAbstractClass(
                'Tenside\\Task\\AbstractComposerCommandTask',
                [new JsonArray(['status' => Task::STATE_PENDING])]
            );

        $command = $this
            ->getMockBuilder('Composer\\Command\\Command')
            ->setConstructorArgs(['testcommand'])
            ->setMethods([])
            ->getMockForAbstractClass();

        $reflection = new \ReflectionMethod($task, 'attachComposerFactory');
        $reflection->setAccessible(true);

        $reflection->invoke($task, $command);
    }
}
