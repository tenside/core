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

namespace Tenside\Config;

/**
 * A sub section of a config.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class SourceSubSection implements SourceInterface
{
    /**
     * The parent config source to use.
     *
     * @var SourceInterface
     */
    private $configSource;

    /**
     * The prefix.
     *
     * @var string
     */
    private $prefix;

    /**
     * Create a new instance.
     *
     * @param SourceInterface $configSource The config instance.
     *
     * @param string          $prefix       The prefix of the config values.
     */
    public function __construct(SourceInterface $configSource, $prefix)
    {
        $this->configSource = $configSource;
        $this->prefix       = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $forceArray = false)
    {
        return $this->configSource->get($this->prefixPath($path), $forceArray);
    }

    /**
     * {@inheritDoc}
     */
    public function has($path)
    {
        return $this->configSource->has($this->prefixPath($path));
    }

    /**
     * {@inheritDoc}
     */
    public function set($path, $value)
    {
        $this->configSource->set($this->prefixPath($path), $value);

        return $this;
    }

    /**
     * Prepend the prefix to the path.
     *
     * @param string $path The path to prepend.
     *
     * @return string
     */
    private function prefixPath($path)
    {
        return $this->prefix . '/' . $path;
    }
}
