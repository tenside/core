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

use Composer\Config;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\RootPackageLoader;
use Composer\Repository\WritableArrayRepository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Composer\ComposerJson;
use Tenside\CoreBundle\Controller\PackageController;

/**
 * Test the abstract controller.
 */
class PackageControllerTest extends TestCase
{
    /**
     * Mock the controller.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageController
     */
    private function prepareController()
    {
        $manager = $this->getMockBuilder('\\Composer\\Repository\\RepositoryManager')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $config = new Config();
        $config->merge(array('repositories' => array('packagist' => false)));

        $loader      = new RootPackageLoader($manager, $config);
        $rootPackage = $loader->load(json_decode($this->readFixture('composer.json'), true));

        $loader   = new ArrayLoader();
        $json     = json_decode($this->readFixture('installed.json'), true);
        $packages = [];
        foreach ($json as $package) {
            $packages[] = $loader->load($package);
        }
        $manager->setLocalRepository(new WritableArrayRepository($packages));

        $composer = $this
            ->getMockBuilder('Composer\\Composer')
            ->setMethods(['getPackage', 'getRepositoryManager'])
            ->getMock();

        $composer->method('getPackage')->willReturn($rootPackage);
        $composer->method('getRepositoryManager')->willReturn($manager);

        $controller = $this
            ->getMockBuilder('Tenside\\CoreBundle\\Controller\\PackageController')
            ->setMethods(['getComposer', 'forward'])
            ->getMock();

        $controller->method('getComposer')->willReturn($composer);

        $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
        $home->method('homeDir')->willReturn($this->getTempDir());

        $composerJson = $this->provideFixture('composer.json');
        $this->provideFixture('composer.lock');
        $this->provideFixture('installed.json', 'vendor/composer/installed.json');

        $container = new Container();
        $container->set('tenside.home', $home);
        $container->set('tenside.composer_json', new ComposerJson($composerJson));

        /** @var PackageController $controller */
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * Test the getComposer() method.
     *
     * @return void
     */
    public function testPackageListAction()
    {
        $controller = $this->prepareController();

        $response = $controller->packageListAction(new Request());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $content = json_decode($response->getContent(), true);

        $this->assertCount(1, $content);
        $this->assertEquals(['vendor/dependency-name'], array_keys($content));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test the getPackageAction() method.
     *
     * @return void
     */
    public function testGetPackageAction()
    {
        $controller = $this->prepareController();

        $response = $controller->getPackageAction('vendor', 'dependency-name');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $content = json_decode($response->getContent(), true);

        $this->assertEquals('vendor/dependency-name', $content['name']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test the getPackageAction() method with an unknown package.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @expectedExceptionMessage Package unknown-vendor/unknown-dependency-name not found.
     */
    public function testGetPackageActionForUnknownBails()
    {
        $controller = $this->prepareController();

        $controller->getPackageAction('unknown-vendor', 'unknown-dependency-name');
    }

    /**
     * Test the getPackageAction() method with a package that has a branch alias.
     *
     * @return void
     */
    public function testGetPackageActionForAliased()
    {
        $controller = $this->prepareController();

        $response = $controller->getPackageAction('vendor', 'unstable-dependency');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $content = json_decode($response->getContent(), true);

        $this->assertEquals('vendor/unstable-dependency', $content['name']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Test the putPackageAction() method with an known package.
     *
     * @return void
     */
    public function testPutPackageAction()
    {
        $data = json_encode([
            'name'       => 'vendor/dependency-name',
            'locked'     => false,
            'constraint' => '>= 10.0'
        ]);

        $controller = $this->prepareController();
        $controller->expects($this->once())->method('forward')->willReturn(null);
        $request = new Request([], [], [], [], [], [], $data);

        $controller->putPackageAction('vendor', 'dependency-name', $request);
    }

    /**
     * Test the putPackageAction() method with an unknown package.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @expectedExceptionMessage Package unknown-vendor/unknown-dependency-name not found.
     */
    public function testPutPackageActionWithUnknown()
    {
        $data = json_encode([
            'name'       => 'unknown-vendor/unknown-dependency-name',
            'locked'     => false,
            'constraint' => '>= 10.0'
        ]);

        $controller = $this->prepareController();

        $request = new Request([], [], [], [], [], [], $data);

        $controller->putPackageAction('unknown-vendor', 'unknown-dependency-name', $request);
    }

    /**
     * Test the putPackageAction() method with an payload missing "constraint.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     *
     * @expectedExceptionMessage Invalid package information.
     */
    public function testPutPackageActionWithPayloadMissingConstraint()
    {
        $data = json_encode([
            'name'       => 'vendor/dependency-name',
            'locked'     => false
        ]);

        $controller = $this->prepareController();

        $request = new Request([], [], [], [], [], [], $data);

        $controller->putPackageAction('vendor', 'dependency-name', $request);
    }

    /**
     * Test the putPackageAction() method with a package with different name.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     *
     * @expectedExceptionMessage Package name mismatch vendor/mismatch-dependency-name vs. vendor/dependency-name.
     */
    public function testPutPackageActionWithNameMismarch()
    {
        $data = json_encode([
            'name'       => 'vendor/dependency-name',
            'locked'     => false,
            'constraint' => '>= 10.0'
        ]);

        $controller = $this->prepareController();

        $request = new Request([], [], [], [], [], [], $data);

        $controller->putPackageAction('vendor', 'mismatch-dependency-name', $request);
    }
}
