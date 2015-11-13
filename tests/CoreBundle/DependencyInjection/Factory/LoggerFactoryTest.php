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

use Tenside\CoreBundle\DependencyInjection\Factory\LoggerFactory;
use Tenside\Test\TestCase;

/**
 * Test the logger factory.
 */
class LoggerFactoryTest extends TestCase
{
    /**
     * Test that the factory creates a new instance.
     *
     * @return void
     */
    public function testCreate()
    {
        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->setMethods(['getLogDir'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $kernel->method('getLogDir')->willReturn($this->getTempDir());

        $logger = LoggerFactory::create($kernel, 'foo.log');

        $this->assertInstanceOf('Monolog\Handler\RotatingFileHandler', $logger);
        $filename = new \ReflectionProperty($logger, 'filename');
        $filename->setAccessible(true);
        $this->assertEquals($this->getTempDir() . DIRECTORY_SEPARATOR . 'foo.log', $filename->getValue($logger));
    }
}
