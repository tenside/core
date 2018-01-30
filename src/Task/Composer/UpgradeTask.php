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
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Symfony\Component\Console\Input\ArrayInput;
use Tenside\Core\Composer\Installer\InstallationManager;
use Tenside\Core\Util\JsonFile;
use Tenside\Core\Util\RuntimeHelper;

/**
 * This class holds the information for an upgrade of some or all packages (composer update).
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
     * The data directory of tenside.
     */
    const SETTING_DATA_DIR = 'data-dir';

    /**
     * The dry-run flag
     */
    const SETTING_DRY_RUN = 'dry-run';

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
     * Retrieve the home path of tenside.
     *
     * @return string
     */
    public function getHome()
    {
        return (string) $this->file->get(self::SETTING_HOME);
    }

    /**
     * Retrieve the data path of tenside.
     *
     * @return string
     */
    public function getDataDir()
    {
        return (string) $this->file->get(self::SETTING_DATA_DIR);
    }

    /**
     * Check if the upgrade is a dry-run.
     *
     * @return bool
     */
    public function isDryRun()
    {
        return (bool) $this->file->get(self::SETTING_DRY_RUN);
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
        RuntimeHelper::setupHome($this->getHome());

        $command = new UpdateCommand();
        $command->setComposer(Factory::create($this->getIO()));

        if ($this->isDryRun()) {
            $pendingUpgrades     = $this->getDataDir() . DIRECTORY_SEPARATOR . 'upgrades.json';
            $installationManager = new InstallationManager(new JsonFile($pendingUpgrades, null));
            $command->getComposer()->setInstallationManager($installationManager);

            $command->getComposer()->getEventDispatcher()->addListener(
                InstallerEvents::PRE_DEPENDENCIES_SOLVING,
                function (InstallerEvent $event) use ($installationManager) {
                    $installationManager->setPool($event->getPool());
                }
            );
        }

        return $command;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInput()
    {
        $arguments = [
            '--prefer-dist' => true,
            '--no-dev' => true,
            '--no-progress' => true,
            '--no-suggest' => true,
            '--no-interaction' => true,
            '--with-dependencies' => true,
            '--optimize-autoloader' => true,
        ];

        if ($this->file->get('debug')) {
            $arguments['--profile'] = true;
        }

        if ($this->isSelectiveUpgrade()) {
            $arguments['packages'] = $this->getPackages();
        }

        if ($this->isDryRun()) {
            $arguments['--dry-run'] = true;
        }

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $input;
    }
}
