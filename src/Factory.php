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

namespace Tenside;

use Composer\Factory as ComposerFactory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Tenside\Config\SourceInterface;
use Tenside\Config\SourceJson;

/**
 * This class creates a populated Tenside instance.
 */
class Factory
{
    /**
     * Create the default json config reader.
     *
     * @param string $path Path to the tenside.json file.
     *
     * @return SourceJson
     */
    protected function getDefaultConfig($path)
    {
        return new SourceJson($path . '/tenside.json');
    }

    /**
     * Determine the default home directory.
     *
     * @return string
     */
    protected function getDefaultHome()
    {
        return dirname(ComposerFactory::getComposerFile());
    }

    /**
     * Create the default input/output handler instance.
     *
     * @return BufferIO
     */
    protected function getDefaultIOHandler()
    {
        return new BufferIO();
    }

    /**
     * Create the tenside instance.
     *
     * @param null|string          $home        The home directory.
     *
     * @param null|SourceInterface $config      The configuration to use.
     *
     * @param null|IOInterface     $inputOutput The IO handler to use.
     *
     * @return Tenside
     */
    public function createTenside($home = null, SourceInterface $config = null, IOInterface $inputOutput = null)
    {
        if (null === $home) {
            $home = realpath(static::getDefaultHome());
        }

        // load Composer configuration
        if (null === $config) {
            $config = static::getDefaultConfig($home);
        }

        if (null === $inputOutput) {
            $inputOutput = static::getDefaultIOHandler();
        }

        $instance = new Tenside();
        $instance
            ->setHome($home)
            ->setConfigSource($config)
            ->setInputOutputHandler($inputOutput);

        return $instance;
    }

    /**
     * Create a tenside instance from default values.
     *
     * @param null|string          $home        The home directory.
     *
     * @param null|SourceInterface $config      The configuration to use.
     *
     * @param null|IOInterface     $inputOutput The IO handler to use.
     *
     * @return Tenside
     */
    public static function create($home = null, SourceInterface $config = null, IOInterface $inputOutput = null)
    {
        $factory = new static();

        return $factory->createTenside($home, $config, $inputOutput);
    }
}
