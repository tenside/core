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

namespace Tenside\Test\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tenside\CoreBundle\DependencyInjection\TensideCoreExtension;
use Tenside\Test\TestCase;

/**
 * Test the extension.
 */
class TensideCoreExtensionTest extends TestCase
{
    /**
     * The instance under test.
     *
     * @var TensideCoreExtension
     */
    protected $extension;

    /**
     * Creates the extension.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->extension = new TensideCoreExtension();
    }

    /**
     * Tests that we have a proper instance
     *
     * @return void
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Tenside\CoreBundle\DependencyInjection\TensideCoreExtension', $this->extension);
    }

    /**
     * Tests adding the bundle services to the container.
     *
     * @return void
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $this->extension->load([], $container);
        $this->assertTrue($container->has('tenside.home'));
        $this->assertTrue($container->has('tenside.cli_script'));
    }

    /**
     * Test that the getAlias() returns 'tenside-core'.
     *
     * @return void
     */
    public function testGetAlias()
    {
        $this->assertEquals('tenside-core', $this->extension->getAlias());
    }
}
