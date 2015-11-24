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

namespace Tenside\Test\CoreBundle\Command;

use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;
use Tenside\CoreBundle\Command\SelfUpdateCommand;
use Tenside\Test\TestCase;

/**
 * Test the self update command.
 */
class SelfUpdateCommandTest extends TestCase
{
    /**
     * Local buffer for $_SERVER['argv'][0].
     *
     * @var string
     */
    private $argv0;

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function setUp()
    {
        $this->argv0 = $_SERVER['argv'][0];

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function tearDown()
    {
        $_SERVER['argv'][0] = $this->argv0;

        parent::tearDown();
    }

    /**
     * Test that the local file name will get determined correctly.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testDetermineLocalFileName()
    {
        $localFile          = $this->createFixture('foo' . DIRECTORY_SEPARATOR . 'phar-file.phar', 'old-version');
        $_SERVER['argv'][0] = $this->getTempDir() . DIRECTORY_SEPARATOR .
            'foo' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'foo' . DIRECTORY_SEPARATOR . 'phar-file.phar';

        $command = new SelfUpdateCommand();

        $reflection = new \ReflectionMethod($command, 'determineLocalFileName');
        $reflection->setAccessible(true);
        $this->assertEquals($localFile, $reflection->invoke($command));
    }

    /**
     * Test that the local file name will return the actual argv0 when the realpath failed.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testDetermineLocalFileNameFallback()
    {
        $localFile = $_SERVER['argv'][0] = $this->getTempDir() . DIRECTORY_SEPARATOR . 'does-not-exist';
        $command   = new SelfUpdateCommand();

        $reflection = new \ReflectionMethod($command, 'determineLocalFileName');
        $reflection->setAccessible(true);
        $this->assertEquals($localFile, $reflection->invoke($command));
    }

    /**
     * Test that the remote file name will get determined correctly.
     *
     * @return void
     */
    public function testDetermineRemoteFileNameFromHash()
    {
        $container = new Container();
        $container->setParameter('tenside.self_update.base_url', 'base-url');
        $container->setParameter('tenside.self_update.phar_name', 'tenside.phar');

        $command = new SelfUpdateCommand();
        $command->setContainer($container);

        $reflection = new \ReflectionMethod($command, 'determineRemoteFilename');
        $reflection->setAccessible(true);
        $this->assertEquals(
            'https://base-url/tenside.phar',
            $reflection->invoke($command, 'bea60b98cab3342203ec94880c03cafa57bb9452')
        );
    }

    /**
     * Test that the remote file name will get determined correctly.
     *
     * @return void
     */
    public function testDetermineRemoteFileNameFromVersion()
    {
        $container = new Container();
        $container->setParameter('tenside.self_update.base_url', 'base-url');
        $container->setParameter('tenside.self_update.phar_name', 'tenside.phar');

        $command = new SelfUpdateCommand();
        $command->setContainer($container);

        $reflection = new \ReflectionMethod($command, 'determineRemoteFilename');
        $reflection->setAccessible(true);
        $this->assertEquals('https://base-url/download/1.0.0/tenside.phar', $reflection->invoke($command, '1.0.0'));
    }

    /**
     * Test normal updating.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testUpdate()
    {
        $localFile = $_SERVER['argv'][0] = $this->createFixture('phar-file.phar', 'old-version');

        $this->provideFixture('self-update' . DIRECTORY_SEPARATOR . 'version');
        $newPhar = $this->provideFixture(
            'self-update' . DIRECTORY_SEPARATOR .
            'download' . DIRECTORY_SEPARATOR .
            '1.0.0' . DIRECTORY_SEPARATOR .
            'tenside.phar'
        );

        $container = new Container();
        $container->setParameter(
            'tenside.self_update.base_url',
            'file://' . $this->getTempDir() . DIRECTORY_SEPARATOR . 'self-update'
        );
        $container->setParameter('tenside.self_update.origin_name', 'tenside.org');
        $container->setParameter('tenside.self_update.phar_name', 'tenside.phar');

        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();
        $command = new SelfUpdateCommand();
        $command->setHelperSet(new HelperSet([]));

        $command->setContainer($container);
        $command->setIO(new ConsoleIO($input, $output, $command->getHelperSet()));

        $this->assertEquals(0, $command->run($input, $output));
        $this->assertFileEquals($newPhar, $localFile);
    }
}
