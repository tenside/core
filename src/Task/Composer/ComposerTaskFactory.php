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

use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This class provides loading of the tenside core configuration.
 */
class ComposerTaskFactory implements TaskFactoryInterface
{
    /**
     * The home path determinator.
     *
     * @var string
     */
    private $home;

    /**
     * Create a new instance.
     *
     * @param string $home The home path to use.
     */
    public function __construct($home)
    {
        $this->home = $home;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported($taskType)
    {
        return in_array($taskType, ['install', 'upgrade', 'require-package', 'remove-package']);
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($taskType, JsonArray $metaData)
    {
        switch ($taskType) {
            case 'install':
                return new InstallTask($metaData);
            case 'upgrade':
                return new UpgradeTask($metaData);
            case 'require-package':
                if (!$metaData->has(RequirePackageTask::SETTING_HOME)) {
                    $metaData->set(RequirePackageTask::SETTING_HOME, $this->home);
                }
                return new RequirePackageTask($metaData);
            case 'remove-package':
                if (!$metaData->has(RemovePackageTask::SETTING_HOME)) {
                    $metaData->set(RemovePackageTask::SETTING_HOME, $this->home);
                }
                return new RemovePackageTask($metaData);
            default:
        }

        throw new \InvalidArgumentException('Do not know how to create task.');
    }
}
