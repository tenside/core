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

namespace Tenside\SelfTest;

/**
 * This class holds all data collected from auto configuration.
 */
class AutoConfig
{
    /**
     * The PHP interpreter to run.
     *
     * @var null|string
     */
    private $phpInterpreter = null;

    /**
     * Command line arguments to add.
     *
     * @var array
     */
    private $commandLineArguments = [];

    /**
     * Retrieve phpInterpreter
     *
     * @return null|string
     */
    public function getPhpInterpreter()
    {
        return $this->phpInterpreter;
    }

    /**
     * Set phpInterpreter.
     *
     * @param null|string $phpInterpreter The new value.
     *
     * @return AutoConfig
     */
    public function setPhpInterpreter($phpInterpreter)
    {
        $this->phpInterpreter = $phpInterpreter;

        return $this;
    }

    /**
     * Retrieve command line arguments.
     *
     * @return array
     */
    public function getCommandLineArguments()
    {
        return $this->commandLineArguments;
    }

    /**
     * Add a command line argument.
     *
     * @param string $argument The argument to add.
     *
     * @return AutoConfig
     */
    public function addCommandLineArgument($argument)
    {
        $this->commandLineArguments[] = $argument;

        return $this;
    }
}
