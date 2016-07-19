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

use Tenside\Core\Task\AbstractTaskFactory;
use Tenside\Core\Util\JsonArray;
use Tenside\Core\Util\HomePathDeterminator;

/**
 * This class provides instantiation of composer command tasks.
 */
class ComposerTaskFactory extends AbstractTaskFactory
{
    /**
     * The home path.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * Create a new instance.
     *
     * @param HomePathDeterminator $home The home path to use.
     */
    public function __construct(HomePathDeterminator $home)
    {
        $this->home = $home;
    }

    /**
     * Create an install task instance.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return InstallTask
     */
    protected function createInstall($metaData)
    {
        return new InstallTask($metaData);
    }

    /**
     * Create an upgrade task instance.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return UpgradeTask
     */
    protected function createUpgrade($metaData)
    {
        $this->ensureHomePath($metaData);
        if (!$metaData->has(UpgradeTask::SETTING_DATA_DIR)) {
            $metaData->set(UpgradeTask::SETTING_DATA_DIR, $this->home->tensideDataDir());
        }
        return new UpgradeTask($metaData);
    }

    /**
     * Create a require package task instance.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return RequirePackageTask
     */
    protected function createRequirePackage($metaData)
    {
        $this->ensureHomePath($metaData);
        return new RequirePackageTask($metaData);
    }

    /**
     * Create a remove package task instance.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return RemovePackageTask
     */
    protected function createRemovePackage($metaData)
    {
        $this->ensureHomePath($metaData);
        return new RemovePackageTask($metaData);
    }

    /**
     * Ensure the home path has been set in the passed meta data.
     *
     * @param JsonArray $metaData The meta data to examine.
     *
     * @return void
     */
    private function ensureHomePath(JsonArray $metaData)
    {
        if ($metaData->has(AbstractPackageManipulatingTask::SETTING_HOME)) {
            return;
        }
        $metaData->set(AbstractPackageManipulatingTask::SETTING_HOME, $this->home->homeDir());
    }
}
