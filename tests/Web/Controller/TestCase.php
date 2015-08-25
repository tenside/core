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
            $tensideHome = sys_get_temp_dir();
        }

        $tenside = new Tenside();
        $tenside
            ->setHome($tensideHome)
            ->setConfigSource(new SourceJson($tensideHome . '/tenside.json'))
            ->setInputOutputHandler(new BufferIO());

        chdir($tenside->getHomeDir());

        return $tenside;
    }
}
