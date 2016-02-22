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

namespace Tenside\Task;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * This task provides the basic framework for building tasks that perform composer commands.
 */
abstract class AbstractPackageManipulatingTask extends AbstractComposerCommandTask
{
    /**
     * The package to install.
     */
    const SETTING_PACKAGE = 'package';

    /**
     * The home path of tenside.
     */
    const SETTING_HOME = 'home';

    /**
     * Retrieve the names of the packages to upgrade or null if none.
     *
     * @return array
     */
    public function getPackage()
    {
        return (array) $this->file->get(self::SETTING_PACKAGE);
    }

    /**
     * Retrieve the home path of tenside.
     *
     * @return string
     */
    public function getHome()
    {
        return $this->file->get(self::SETTING_HOME);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInput()
    {
        $input = new ArrayInput(['packages' => $this->getPackage()]);
        $input->setInteractive(false);

        return $input;
    }
}
