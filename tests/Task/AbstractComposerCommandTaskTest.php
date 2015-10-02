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

use Composer\Command\Command;
use Composer\Composer;
use Composer\Factory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tenside\Task\AbstractComposerCommandTask;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;
use Tenside\Util\RuntimeHelper;

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
                [new JsonArray()]
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
                [new JsonArray()]
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
                [new JsonArray()]
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
}
