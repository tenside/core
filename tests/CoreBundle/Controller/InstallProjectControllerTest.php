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

namespace Tenside\Test\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Tenside\CoreBundle\Controller\InstallProjectController;

/**
 * Test the create-project command of composers
 */
class InstallProjectControllerTest extends TestCase
{
    /**
     * Tests the create project when already installed.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     */
    public function testAlreadyInstalledException()
    {
        $this->provideFixture('composer.json');
        $this->provideFixture('tenside.json');
        $controller = new InstallProjectController();
        $controller->setContainer($this->createDefaultContainer());

        $controller->createProjectAction(new Request());
    }

    /**
     * Tests the create project action.
     *
     * @return void
     */
    public function testInstalledProject()
    {
        $passwordEncoder = $this
            ->getMockForAbstractClass('Symfony\\Component\\Security\\Core\\Encoder\\UserPasswordEncoderInterface');
        $passwordEncoder->method('encodePassword')->willReturn('s3cret');
        $passwordEncoder->method('isPasswordValid')->willReturn(true);

        $userProvider = $this
            ->getMock(
                'Tenside\\CoreBundle\\Security\\UserProviderFromConfig',
                [],
                [$this->getMockForAbstractClass('Tenside\\Config\\SourceInterface')]
            );
        $userProvider->expects($this->once())->method('addUser')->willReturn($userProvider);

        $controller = $this->getMock(
            'Tenside\\CoreBundle\\Controller\\InstallProjectController',
            ['generateUrl']
        );
        $controller->method('generateUrl')->willReturn('http://url/to/task');

        /** @var $controller InstallProjectController */

        $controller->setContainer(
            $container = $this->createDefaultContainer([
                'security.password_encoder' => $passwordEncoder,
                'tenside.user_provider'     => $userProvider
            ])
        );

        $request = new Request([], [], [], [], [], [], json_encode(['project' =>
            [
                'name'      => 'contao/standard-edition',
                'version'   => '4.0.0',
            ]
        ]));

        $response = $controller->createProjectAction($request);
        $data     = json_decode($response->getContent(), true);

        $this->assertEquals('OK', $data['status']);
        $this->assertEquals('http://url/to/task', $response->headers->get('Location'));
        $this->assertCount(1, $container->get('tenside.tasks')->getIds());
        $this->assertEquals($container->get('tenside.tasks')->getIds()[0], $data['task']);
    }
}
