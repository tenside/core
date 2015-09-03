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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Test\Web\Controller;

use Composer\IO\BufferIO;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Config\SourceJson;
use Tenside\Tenside;
use Tenside\Test\TestCase as BaseTestCase;
use Tenside\Web\Application;
use Tenside\Web\Controller\AbstractController;

/**
 * Base test case for testing controllers.
 */
class TestCase extends BaseTestCase
{
    /**
     * Mock the application including tenside and the session.
     *
     * @param Tenside $tenside    The tenside instance.
     *
     * @param string  $cliCommand The command to use for starting cli context.
     *
     * @return Application
     */
    protected function mockDefaultApplication(Tenside $tenside, $cliCommand = '/bin/false')
    {
        $application = $this->getMock('Tenside\\Web\\Application', null, [$cliCommand]);

        /** @var Application $application */
        $application->setTenside($tenside);

        return $application;
    }

    /**
     * Returns the default tenside instance.
     *
     * @param string $tensideHome The tenside home.
     *
     * @return Tenside
     */
    protected function createDefaultTensideInstance($tensideHome = null)
    {
        if (null === $tensideHome) {
            $tensideHome = $this->getTempDir();
        }

        $tenside = new Tenside();
        $tenside
            ->setHome($tensideHome)
            ->setConfigSource(new SourceJson($tensideHome . '/tenside.json'))
            ->setInputOutputHandler(new BufferIO());

        chdir($tenside->getHomeDir());

        return $tenside;
    }

    /**
     * Create a request for the given action.
     *
     * @param string $methodName The method to create the request for.
     *
     * @param array  $parameters The parameters for the request.
     *
     * @return Request
     */
    protected function createRequestFor($methodName, $parameters = [])
    {
        if ('Action' !== substr($methodName, -6)) {
            $this->fail('Invalid method name ' . $methodName . ' probably should be named ' . $methodName . 'Action?');
        }

        $request = new Request();
        $request->attributes->set('_route', substr($methodName, 0, -6));
        $request->attributes->set('_route_params', $parameters);

        return $request;
    }

    /**
     * Provide the list of all methods and routes to be registered.
     *
     * @return array
     */
    public function allRoutesRegisteredProvider()
    {
        $controllerUnderTest = preg_replace('#\\\\?Test#', '', get_class($this));
        $reflection          = new ReflectionClass($controllerUnderTest);

        // Do not attempt to test abstract controllers.
        if ($reflection->isAbstract()) {
            return [['skipped', 'skipped']];
        }

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            // not an action handler? Next please!
            if ('Action' !== substr($method->getName(), -6) || $method->isAbstract()) {
                continue;
            }

            $methods[] = $method;
        }

        return array_map(function ($method) use ($controllerUnderTest) {
            return [
                $method,
                $controllerUnderTest
            ];
        }, $methods);
    }

    /**
     * Test that the controller registers all routes.
     *
     * @param ReflectionMethod $method              The method name being searched.
     *
     * @param string           $controllerUnderTest The name of the controller class being tested.
     *
     * @return void
     *
     * @dataProvider allRoutesRegisteredProvider
     */
    public function testAllRoutesCorrectlyRegistered($method, $controllerUnderTest)
    {
        if ('skipped' === $method) {
            return;
        }

        $routes = new RouteCollection();
        /** @var AbstractController $controllerUnderTest */
        $controllerUnderTest::createRoutes($routes);

        $methodName = $controllerUnderTest . '::' . $method->getName();

        $found = false;
        foreach ($routes->all() as $name => $route) {
            if ($name !== substr($method->getName(), 0, -6)) {
                continue;
            }

            $found = true;

            // Filter all internal requirements.
            $parameterNames = array_filter(array_keys($route->getRequirements()), function ($name) {
                return '_' !== $name[0];
            });
            $parameters = $method->getParameters();

            /** @var ReflectionParameter $last */
            $last = end($parameters);
            if (false !== $last && $this->isRequestParameter($last)) {
                array_pop($parameters);
            }

            foreach ($parameters as $key => $parameter) {
                $this->assertFalse(
                    $this->isRequestParameter($parameter),
                    'The request parameter must be the last one in ' . $methodName
                );

                $this->assertEquals(
                    $parameter->name,
                    $parameterNames[$key],
                    'Parameter name mismatch for parameter ' . $key . ' in ' . $methodName
                );
            }

            if (count($parameters) !== count($parameterNames)) {
                var_export($route->getRequirements());
            }

            $this->assertEquals(
                count($parameters),
                count($parameterNames),
                'Parameter count mismatch'
            );
        }

        $this->assertTrue($found, 'No route registered for method ' . $methodName);

        $this->assertTrue(
            $method->isProtected(),
            $methodName . ' must be protected (currently is ' . $this->getVisibility($method) . ').'
        );
    }

    /**
     * Return the visibility of a method.
     *
     * @param ReflectionMethod $method The method to check.
     *
     * @return string
     */
    private function getVisibility(ReflectionMethod $method)
    {
        switch (true) {
            case $method->isPrivate():
                return 'private';
            case $method->isProtected():
                return 'protected';
            case $method->isPublic():
                return 'public';
            default:
        }

        return '--unknown--';
    }

    /**
     * Check if the parameter is the request parameter.
     *
     * @param ReflectionParameter $parameter The parameter to check.
     *
     * @return bool
     */
    private function isRequestParameter(ReflectionParameter $parameter)
    {
        return 'Symfony\Component\HttpFoundation\Request' === $this->getAnnotatedTypeFromParameter($parameter);
    }

    /**
     * Extract the annotated type from the parameter
     *
     * @param ReflectionParameter $parameter The parameter to check.
     *
     * @return string|null
     */
    private function getAnnotatedTypeFromParameter(ReflectionParameter $parameter)
    {
        preg_match('/\[\s\<\w+?>\s([\w\\\\]+)/s', $parameter->__toString(), $matches);

        return isset($matches[1]) ? $matches[1] : null;
    }
}
