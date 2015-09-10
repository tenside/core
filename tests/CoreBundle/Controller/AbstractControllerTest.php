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

namespace Tenside\Test\CoreBundle\Controller;

use Tenside\CoreBundle\Controller\AbstractController;

/**
 * Test the abstract controller.
 */
class AbstractControllerTest extends TestCase
{
    /**
     * Test the getTenside() method.
     *
     * @return void
     */
    public function testGetTenside()
    {
        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\AbstractController')
            ->setMethods(null)
            ->getMockForAbstractClass();
        $container  = $this->createDefaultContainer();
        /** @var AbstractController $controller */
        $controller->setContainer($container);

        $this->assertEquals($container->get('tenside'), $controller->getTenside());
    }
}
