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
        // FIXME: could be abstract?
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
        $methods = ['GET'],
        $requirements = []
    ) {
        $route = new Route(
            $path, ['_controller' => get_called_class() . '::handle'],
            $requirements,
            [],
            '',
            [],
            $methods,
            ''
        );

        $routes->add($name, $route);

        return $route;
    }

    /**
     * Determine the method to call.
     *
     * @param Request $request The request being processed.
     *
     * @return string|null
     */
    private function determineMethod(Request $request)
    {
        $route = $request->attributes->get('_route');
        if (!empty($route)) {
            $method = $request->attributes->get('_route') . 'Action';
            if (method_exists($this, $method)) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Determine the argument list to use.
     *
     * @param Request $request The request being processed.
     *
     * @return string|null
     */
    private function getFunctionArguments(Request $request)
    {
        $args = [];
        if ($request->attributes->has('_route_params')) {
            $args = $request->attributes->get('_route_params');
        }
        $args[] = $request;

        return $args;
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
        $method = $this->determineMethod($request);
        if (!empty($method)) {
            return call_user_func_array([$this, $method], $this->getFunctionArguments($request));
        }

        return new Response('Bad request: ' . $request->getUri(), Response::HTTP_BAD_REQUEST);
    }
}
