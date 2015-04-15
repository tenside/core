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

namespace Tenside\Web;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Factory;
use Tenside\Tenside;
use Tenside\Ui\Web\Controller\UiController;
use Tenside\Web\Controller\AbstractController;
use Tenside\Web\Controller\AuthController;
use Tenside\Web\Controller\ComposerJsonController;
use Tenside\Web\Controller\PackageController;
use Tenside\Web\Exception\LoginRequiredException;

/**
 * The web application.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class Application
{
    /**
     * The request.
     *
     * @var Request
     */
    protected $request;

    /**
     * The user session.
     *
     * @var Session
     */
    protected $session;

    /**
     * The tenside instance.
     *
     * @var Tenside
     */
    private $tenside;

    /**
     * Retrieve the user session.
     *
     * @return Session
     */
    public function getSession()
    {
        if (!isset($this->session)) {
            $this->session = new Session();
            $this->session->start();
        }

        return $this->session;
    }

    /**
     * Set the Tenside instance to use.
     *
     * @param Tenside $tenside The instance.
     *
     * @return Application
     */
    public function setTenside($tenside)
    {
        $this->tenside = $tenside;

        return $this;
    }

    /**
     * Retrieve the tenside instance.
     *
     * @return Tenside
     */
    public function getTenside()
    {
        if (!$this->tenside) {
            $this->tenside = Factory::create();
        }

        return $this->tenside;
    }

    /**
     * Retrieve the user information.
     *
     * @return UserInformation|null
     */
    public function getAuthenticatedUser()
    {
        if ($this->getSession()->has('user')) {
            return $this->getSession()->get('user');
        }

        return null;
    }

    /**
     * Set the user information.
     *
     * @param UserInformation|null $user The user.
     *
     * @return void
     */
    public function setAuthenticatedUser($user)
    {
        $this->getSession()->set('user', $user);
    }

    /**
     * Check if the session is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->getAuthenticatedUser() !== null;
    }

    /**
     * Create the controllers and add the routes into the route collection.
     *
     * @param RouteCollection $routes The route collection.
     *
     * @return void
     */
    public function addRoutes(RouteCollection $routes)
    {
        // FIXME: in the compiler we must prebuild the routes and only load them here from within the phar.
        // This should be much like the container building in plain symfony apps.
        AuthController::createRoutes($routes);
        ComposerJsonController::createRoutes($routes);
        // FIXME: hard dependency on the ui package - must be resolved.
        if (class_exists('Tenside\\Ui\\Web\\Controller\\UiController')) {
            UiController::createRoutes($routes);
        }
        PackageController::createRoutes($routes);
    }

    /**
     * Create the request object.
     *
     * @return Request
     */
    protected function getRequest()
    {
        // Create the Request object if none injected.
        if (!$this->request) {
            $this->setRequest(Request::createFromGlobals());
        }

        return $this->request;
    }

    /**
     * Set the current request to handle.
     *
     * @param Request $request The request.
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function run()
    {
        $this->setupHome();
        $this->tenside = Factory::create();

        // FIXME: expunge "old" sessions.
        $this->getSession();

        $dispatcher = new EventDispatcher();
        $routes     = new RouteCollection();
        $resolver   = new ControllerResolver();
        $request    = $this->getRequest();
        $context    = new RequestContext();
        $matcher    = new UrlMatcher($routes, $context);
        $context->fromRequest($request);
        $dispatcher->addSubscriber(new RouterListener($matcher));
        $dispatcher->addListener(KernelEvents::CONTROLLER, function (FilterControllerEvent $event) {
            if (!is_array($event->getController())) {
                return;
            }
            $controller = $event->getController()[0];
            if (!$controller instanceof AbstractController) {
                return;
            }
            /** @var AbstractController $controller */
            $controller->setApplication($this);
        });
        $this->addRoutes($routes);

        // FIXME: Add a cycle here to check installed.json for tenside-plugins and boot them here.
        // Let them register events, routes, ...

        // instantiate the kernel
        $kernel = new HttpKernel($dispatcher, $resolver);

        // Actually execute the kernel, which turns the request into a response
        // by dispatching events, calling a controller, and returning the response.
        try {
            $response = $kernel->handle($request);
            // FIXME: These should be response exception listeners.
        } catch (NotFoundHttpException $exception) {
            $response = $this->createNotFoundResponse();
        } catch (LoginRequiredException $exception) {
            $response = $this->createUnauthorizedResponse();
        } catch (\Exception $exception) {
            $response = $this->createInternalServerError($exception);
        }

        // FIXME: do a real CSRF token here.
        $response->headers->setCookie(new Cookie('CSRF-TOKEN', 'abc'));
        $response->send();

        $kernel->terminate($request, $response);
    }

    /**
     * Create a 401 exception when no user is logged in.
     *
     * @return void
     *
     * @throws LoginRequiredException When no user is logged in.
     */
    public function ensureAuthenticated()
    {
        if (!$this->isAuthenticated()) {
            throw new LoginRequiredException();
        }
    }

    /**
     * Create a 404 response.
     *
     * @return Response
     */
    private function createNotFoundResponse()
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_NOT_FOUND] . $this->getRequest()->getRequestUri(),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Create a 401 response.
     *
     * @return Response
     */
    private function createUnauthorizedResponse()
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Create a 500 response.
     *
     * @return Response
     */
    private function createInternalServerError($exception)
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR] . $exception,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Detect the correct tenside home dir and set the environment variable.
     *
     * @return void
     *
     * @throws \RuntimeException When the home directory is not /web.
     */
    private function setupHome()
    {
        if ('' !== \Phar::running()) {
            $home = \Phar::running();
        } else {
            // FIXME: really only one up?
            $home = getcwd();
        }

        if (substr($home, -4) !== '/web') {
            throw new \RuntimeException('Tenside is intended to be run from within the web directory.');
        }

        putenv('COMPOSER_HOME=' . dirname($home));
        chdir(dirname($home));
    }
}
