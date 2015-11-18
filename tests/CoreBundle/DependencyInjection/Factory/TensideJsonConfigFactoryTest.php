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

use Tenside\CoreBundle\DependencyInjection\Factory\TensideJsonConfigFactory;
use Tenside\Test\TestCase;

/**
 * Test the tenside.json factory.
 */
class TensideJsonConfigFactoryTest extends TestCase
{
    /**
     * Test that the factory creates a new instance.
     *
     * @return void
     */
    public function testCreate()
    {
        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['tensideDataDir']);
        $home->method('tensideDataDir')->willReturn($this->getTempDir());
        /** @var \Tenside\CoreBundle\HomePathDeterminator $home */

        $tenside = TensideJsonConfigFactory::create($home);

        $this->assertInstanceOf('Tenside\CoreBundle\TensideJsonConfig', $tenside);

        $jsonFile = new \ReflectionProperty($tenside, 'jsonFile');
        $jsonFile->setAccessible(true);
        $diskFile = $jsonFile->getValue($tenside);

        $this->assertEquals($this->getTempDir() . DIRECTORY_SEPARATOR . 'tenside.json', $diskFile->getFilename());
    }
}
