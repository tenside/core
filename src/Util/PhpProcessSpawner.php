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

namespace Tenside\Core\Util;

use Symfony\Component\Process\Process;
use Tenside\Core\Config\TensideJsonConfig;

/**
 * This class implements a php process spawner.
 */
class PhpProcessSpawner
{
    /**
     * The configuration in use.
     *
     * @var TensideJsonConfig
     */
    private $config;

    /**
     * The home directory to run the process in.
     *
     * @var string
     */
    private $homePath;

    /**
     * Create a new instance.
     *
     * @param TensideJsonConfig $config   The configuration in use.
     *
     * @param string            $homePath The directory to use as home directory.
     */
    public function __construct(TensideJsonConfig $config, $homePath)
    {
        $this->config   = $config;
        $this->homePath = $homePath;
    }

    /**
     * Create a new instance.
     *
     * @param TensideJsonConfig $config   The configuration in use.
     *
     * @param string            $homePath The directory to use as home directory.
     *
     * @return PhpProcessSpawner
     */
    public static function create(TensideJsonConfig $config, $homePath)
    {
        return new static($config, $homePath);
    }

    /**
     * Create the process (automatically forcing to background if configured).
     *
     * @param array $arguments The additional arguments to add to the call.
     *
     * @return Process
     */
    public function spawn(array $arguments)
    {
        return $this
            ->buildInternal($arguments)
            ->setForceBackground($this->config->isForceToBackgroundEnabled())
            ->generate();
    }

    /**
     * Build the internal process builder.
     *
     * @param array $arguments The arguments.
     *
     * @return ProcessBuilder
     */
    private function buildInternal(array $arguments)
    {
        $builder = ProcessBuilder::create($this->config->getPhpCliBinary());

        if (null !== ($cliArguments = $this->config->getPhpCliArguments())) {
            $builder->addArguments($cliArguments);
        }
        $builder->addArguments($arguments);

        if (null !== ($environment = $this->config->getPhpCliEnvironment())) {
            foreach ($environment as $name => $value) {
                $builder->setEnv($name, $value);
            }
        }
        // MUST be kept last.
        $builder->setEnv('COMPOSER', $this->homePath . DIRECTORY_SEPARATOR . 'composer.json');

        return $builder;
    }
}
