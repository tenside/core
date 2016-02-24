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

namespace Tenside\Core\Task\Composer;

use Composer\Command\UpdateCommand;
use Composer\Factory;
use Symfony\Component\Console\Input\ArrayInput;
use Tenside\Core\Util\RuntimeHelper;

/**
 * This class holds the information for an upgrade of some or all packages.
 */
class UpgradeTask extends AbstractComposerCommandTask
{
    /**
     * The packages to upgrade.
     */
    const SETTING_PACKAGES = 'packages';

    /**
     * The home path of tenside.
     */
    const SETTING_HOME = 'home';

    /**
     * Retrieve the names of the packages to upgrade or null if none.
     *
     * @return string[]|null
     */
    public function getPackages()
    {
        return $this->file->get(self::SETTING_PACKAGES);
    }

    /**
     * Check if the upgrade is selective or for all packages.
     *
     * @return bool
     */
    public function isSelectiveUpgrade()
    {
        return (null !== $this->getPackages());
    }

    /**
     * Returns 'upgrade'.
     *
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'upgrade';
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareCommand()
    {
        RuntimeHelper::setupHome($this->file->get(self::SETTING_HOME));

        $command = new UpdateCommand();
        $command->setComposer(Factory::create($this->getIO()));

        return $command;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInput()
    {
        $arguments = [];

        if ($this->isSelectiveUpgrade()) {
            $arguments['packages'] = $this->getPackages();
        }

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $input;
    }
}
