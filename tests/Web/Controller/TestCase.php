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

namespace Tenside\Test\Web\Controller;

use Composer\IO\BufferIO;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tenside\Config\SourceJson;
use Tenside\Tenside;
use Tenside\Web\Application;

/**
 * Test the composer.json manipulation controller.
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock the application including tenside and the session.
     *
     * @param null|string $tensideHome The home dir to use.
     *
     * @param null|string $sessionId   The session id.
     *
     * @return Application
     */
    protected function mockApplication($tensideHome = null, $sessionId = null)
    {
        if (null === $tensideHome) {
            $tensideHome = sys_get_temp_dir();
        }
        chdir($tensideHome);

        $application = $this->getMock('Tenside\\Web\\Application', ['getSession']);
        $tenside     = new Tenside();
        $tenside
            ->setHome($tensideHome)
            ->setConfigSource(new SourceJson($tensideHome . '/tenside.json'))
        ->setInputOutputHandler(new BufferIO());

        $session = new Session(new MockArraySessionStorage());
        $session->setId($sessionId);
        $session->start();
        $application
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($session));

        /** @var Application $application */
        $application->setTenside($tenside);

        return $application;
    }
}
