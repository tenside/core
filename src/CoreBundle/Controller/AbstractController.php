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

namespace Tenside\CoreBundle\Controller;

use Composer\Composer;
use Composer\Factory as ComposerFactory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Tenside\CoreBundle\TensideJsonConfig;
use Tenside\Task\TaskList;
use Tenside\Tenside;
use Tenside\Util\RuntimeHelper;

/**
 * Abstract controller class.
 */
abstract class AbstractController extends Controller
{
    /**
     * Retrieve a composer instance.
     *
     * @param IOInterface $inputOutput The input/output handler to use.
     *
     * @return Composer
     */
    public function getComposer(IOInterface $inputOutput = null)
    {
        if (null === $inputOutput) {
            $inputOutput = new BufferIO();
        }
        RuntimeHelper::setupHome($this->getTensideHome());

        return ComposerFactory::create($inputOutput);
    }

    /**
     * Retrieve the tenside instance.
     *
     * @return Tenside
     *
     * @deprecated The tenside class will get phased out to be a simple version constant container.
     */
    public function getTenside()
    {
        return $this->container->get('tenside');
    }

    /**
     * Retrieve the tenside instance.
     *
     * @return TensideJsonConfig
     */
    public function getTensideConfig()
    {
        return $this->container->get('tenside.config');
    }

    /**
     * Retrieve the tenside instance.
     *
     * @return string
     */
    public function getTensideHome()
    {
        return $this->container->get('tenside.home')->homeDir();
    }

    /**
     * Retrieve the tenside task list.
     *
     * @return TaskList
     */
    public function getTensideTasks()
    {
        return $this->container->get('tenside.tasks');
    }
}
