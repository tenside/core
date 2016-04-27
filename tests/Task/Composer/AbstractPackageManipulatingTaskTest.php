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

namespace Tenside\Core\Test\Task\Composer;

use Symfony\Component\Console\Input\InputInterface;
use Tenside\Core\Task\Composer\AbstractPackageManipulatingTask;
use Tenside\Core\Task\Task;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonArray;

/**
 * This class tests the abstract package manipulating task.
 */
class AbstractPackageManipulatingTaskTest extends TestCase
{
    /**
     * Test setting the composer factory on invalid commands raises an exception.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testPassingNoUpdateWorks()
    {
        $task = $this
            ->getMockForAbstractClass(
                AbstractPackageManipulatingTask::class,
                [new JsonArray([
                    'status' => Task::STATE_PENDING,
                    AbstractPackageManipulatingTask::SETTING_NO_UPDATE => true
                ])]
            );

        $reflection = new \ReflectionMethod($task, 'prepareInput');
        $reflection->setAccessible(true);
        /** @var InputInterface $input */
        $input = $reflection->invoke($task);

        $this->assertTrue($input->getOption('no-update'));
    }
}
