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

namespace Tenside\Test\CoreBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Tenside\CoreBundle\TensideCoreBundle;
use Tenside\Test\TestCase;

/**
 * Test the bundle.
 */
class TensideCoreBundleTest extends TestCase
{
    /**
     * Test that calling getContainerExtension always creates a new extension.
     *
     * @return void
     */
    public function testGetContainerExtensionCreatesExtension()
    {
        $bundle = new TensideCoreBundle();

        $this->assertInstanceOf(
            'Tenside\CoreBundle\DependencyInjection\TensideCoreExtension',
            $first = $bundle->getContainerExtension()
        );

        $this->assertInstanceOf(
            'Tenside\CoreBundle\DependencyInjection\TensideCoreExtension',
            $second = $bundle->getContainerExtension()
        );

        $this->assertNotSame($first, $second);
    }

    /**
     * Test that calling boot() registers our annotation in the AnnotationRegistry.
     *
     * @return void
     */
    public function testBootRegistersAnnotationInLoader()
    {
        $bundle = new TensideCoreBundle();

        $this->assertFalse(AnnotationRegistry::loadAnnotationClass('Tenside\CoreBundle\Annotation\ApiDescription'));
        $this->assertFalse(class_exists('Tenside\CoreBundle\Annotation\ApiDescription', false));

        $bundle->boot();
        $this->assertFalse(AnnotationRegistry::loadAnnotationClass('NonExistant\\Annotation'));
        // Ensure the class does not get loaded by requiring anoter annotation.
        $this->assertFalse(class_exists('Tenside\CoreBundle\Annotation\ApiDescription', false));

        $this->assertTrue(AnnotationRegistry::loadAnnotationClass('Tenside\CoreBundle\Annotation\ApiDescription'));
        $this->assertTrue(class_exists('Tenside\CoreBundle\Annotation\ApiDescription', false));
    }
}
