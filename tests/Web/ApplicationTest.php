<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Test\Web;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Web\Application;

/**
 * Test the application.
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test a single route.
     *
     * @param string $expectedName           The expected route name.
     *
     * @param string $expectedControllerType The expected controller class.
     *
     * @param array  $actual                 The actual matched route.
     *
     * @return void
     */
    protected function assertRoute($expectedName, $expectedControllerType, $actual)
    {
        $this->assertEquals($expectedName, $actual['_route']);
        $this->assertEquals($expectedControllerType, explode('::', $actual['_controller'])[0]);
    }

    /**
     * Test that all routes match.
     *
     * @return void
     */
    public function testRoutes()
    {
        $application = new Application();
        $routes      = new RouteCollection();
        $matcher     = new UrlMatcher($routes, new RequestContext());
        $application->addRoutes($routes);

        $this->assertRoute(
            'checkAuth',
            'Tenside\Web\Controller\AuthController',
            $matcher->match('/auth')
        );
        $this->assertRoute(
            'getComposerJson',
            'Tenside\Web\Controller\ComposerJsonController',
            $matcher->match('/api/v1/composer.json')
        );
    }
}
