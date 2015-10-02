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

namespace Tenside\Task\WrappedCommand;

use Composer\Composer;

/**
 * This class provides a simple way to ad-hoc create a composer instance from a command via a factory closure.
 */
trait WrappedCommandTrait
{
    /**
     * The current composer instance.
     *
     * @var Composer
     */
    private $composer;

    /**
     * The factory method to use for creating a composer instance.
     *
     * @var \Closure
     */
    private $composerFactory;

    /**
     * Retrieve the composer instance.
     *
     * @param bool $required       Flag if the instance is required.
     *
     * @param bool $disablePlugins Flag if plugins shall get disabled.
     *
     * @return Composer|null
     *
     * @throws \RuntimeException When no factory closure has been set.
     */
    public function getComposer($required = true, $disablePlugins = false)
    {
        if (null === $this->composer) {
            if ($this->composerFactory) {
                $this->composer = call_user_func($this->composerFactory, $required, $disablePlugins);
            }

            if ($required && !$this->composer) {
                throw new \RuntimeException(
                    'You must define a factory closure for wrapped commands to retrieve the ' .
                    'composer instance.'
                );
            }
        }

        return $this->composer;
    }

    /**
     * Save the composer instance to use.
     *
     * @param Composer $composer The instance to use.
     *
     * @return void
     */
    public function setComposer(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Removes the cached composer instance.
     *
     * @return void
     */
    public function resetComposer()
    {
        $this->composer = null;
    }

    /**
     * Set the composer factory closure.
     *
     * @param \Closure $composerFactory The new factory function.
     *
     * @return RequireCommand
     */
    public function setComposerFactory($composerFactory)
    {
        $this->composerFactory = $composerFactory;

        return $this;
    }
}
