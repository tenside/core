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

namespace Tenside\Web\Controller;

use Composer\IO\IOInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Tenside;
use Tenside\Web\Application;

/**
 * Abstract controller class.
 */
abstract class AbstractController
{
    /**
     * The application.
     *
     * @var Application
     */
    private $application;

    /**
     * Set the application.
     *
     * @param Application $application The application.
     *
     * @return AbstractController
     */
    public function setApplication($application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Retrieve the application.
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Retrieve the io instance.
     *
     * @return IOInterface
     */
    public function getInputOutputHandler()
    {
        return $this->getTenside()->getInputOutputHandler();
    }

    /**
     * Retrieve the tenside instance.
     *
     * @return Tenside
     */
    public function getTenside()
    {
        return $this->getApplication()->getTenside();
    }

    /**
     *  Create the routes in the given route collection.
     *
     * @param RouteCollection $routes The route collection to add routes to.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function createRoutes(RouteCollection $routes)
    {
        // No-op.
    }

    /**
     *  Create the routes in the given route collection.
     *
     * @param RouteCollection $routes       The route collection to add routes to.
     *
     * @param string          $name         The name of the route.
     *
     * @param string          $path         The path pattern to match.
     *
     * @param string          $controller   The controller class name (must be derived from AbstractController.
     *
     * @param string|array    $methods      A required HTTP method or an array of restricted methods.
     *
     * @param array           $requirements An array of requirements for parameters (regexes).
     *
     * @return Route
     */
    protected static function createRoute(
        RouteCollection $routes,
        $name,
        $path,
        $controller,
        $methods = ['GET'],
        $requirements = []
    ) {
        $route = new Route($path, ['_controller' => $controller . '::handle'], $requirements, [], '', [], $methods, '');

        $routes->add($name, $route);

        return $route;
    }

    /**
     * Handle the request.
     *
     * @param Request $request The request to process.
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $method = $request->attributes->get('_route') . 'Action';
        if (method_exists($this, $method)) {
            $args = [];
            if ($request->attributes->has('_route_params')) {
                $args = $request->attributes->get('_route_params');
            }
            $args[] = $request;
            return call_user_func_array([$this, $method], $args);
        }

        return new Response('Bad request: ' . $request->getUri(), Response::HTTP_BAD_REQUEST);
    }
}
