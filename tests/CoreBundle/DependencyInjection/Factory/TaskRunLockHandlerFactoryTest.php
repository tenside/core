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

namespace Tenside\Test\CoreBundle\DependencyInjection\Factory;

use Tenside\CoreBundle\DependencyInjection\Factory\TaskRunLockHandlerFactory;
use Tenside\Test\TestCase;

/**
 * Test the composer.json factory.
 */
class TaskRunLockHandlerFactoryTest extends TestCase
{
    /**
     * Test that the factory creates a new instance.
     *
     * @return void
     */
    public function testCreate()
    {
        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $lockHandler = TaskRunLockHandlerFactory::create($home);

        $this->assertInstanceOf('Symfony\Component\Filesystem\LockHandler', $lockHandler);

        $filename = new \ReflectionProperty($lockHandler, 'file');
        $filename->setAccessible(true);
        $diskFile = $filename->getValue($lockHandler);
        $this->assertEquals($home->tensideDataDir(), dirname($diskFile));
        $this->assertTrue(false !== strpos($diskFile, 'task-run'));
    }
}
