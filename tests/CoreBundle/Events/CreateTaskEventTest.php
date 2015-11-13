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

namespace Tenside\Test\CoreBundle\Events;

use Tenside\CoreBundle\Events\CreateTaskEvent;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * Test the application.
 */
class CreateTaskEventTest extends TestCase
{
    /**
     * Test all methods.
     *
     * @return void
     */
    public function testAll()
    {
        $event = new CreateTaskEvent($metaData = new JsonArray());

        $this->assertSame($metaData, $event->getMetaData());
        $this->assertNull($event->getTask());
        $this->assertFalse($event->hasTask());
        $this->assertSame(
            $event,
            $event->setTask($task = $this->getMockForAbstractClass('Tenside\Task\Task', [$metaData]))
        );
        $this->assertSame($task, $event->getTask());
        $this->assertTrue($event->hasTask());
    }
}
