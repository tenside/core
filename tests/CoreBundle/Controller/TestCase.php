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

use Symfony\Component\DependencyInjection\Container;
use Tenside\Composer\ComposerJsonFactory;
use Tenside\CoreBundle\TensideJsonConfig;
use Tenside\Tenside;
use Tenside\Test\TestCase as BaseTestCase;

/**
 * Base test case for testing controllers.
 */
class TestCase extends BaseTestCase
{
    /**
     * Create the default container containing all basic services.
     *
     * @param array $services Array of services to provide.
     *
     * @return Container
     */
    protected function createDefaultContainer($services = [])
    {
        $container = new Container();

        foreach ($services as $name => $service) {
            $container->set($name, $service);
        }

        if (!$container->has('tenside.home')) {
            $home = $this->getMock('Tenside\\CoreBundle\\HomePathDeterminator', ['homeDir']);
            $home->method('homeDir')->willReturn($this->getTempDir());
            $container->set('tenside.home', $home);
        }

        if (!$container->has('tenside.config')) {
            $container->set('tenside.config', new TensideJsonConfig($container->get('tenside.home')));
        }

        if (!$container->has('tenside.composer_json')) {
            $container->set('tenside.composer_json', ComposerJsonFactory::create($container->get('tenside.home')));
        }

        if (!$container->has('tenside')) {
            $tenside = new Tenside($container->get('tenside.home'));
            $container->set('tenside', $tenside);
        }

        return $container;
    }
}
