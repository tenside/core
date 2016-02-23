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

namespace Tenside\CoreBundle\EventListener;

use Tenside\CoreBundle\Events\CreateTaskEvent;
use Tenside\CoreBundle\HomePathDeterminator;
use Tenside\Task\InstallTask;
use Tenside\Task\RemovePackageTask;
use Tenside\Task\RequirePackageTask;
use Tenside\Task\Task;
use Tenside\Task\UpgradeTask;

/**
 * This class provides loading of the tenside core configuration.
 */
class TaskListener
{
    /**
     * The home path determinator.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * Create a new instance.
     *
     * @param HomePathDeterminator $home The home path determinator.
     */
    public function __construct($home)
    {
        $this->home = $home;
    }

    /**
     * Handle the event.
     *
     * @param CreateTaskEvent $event The event.
     *
     * @return void
     */
    public function onCreateTask(CreateTaskEvent $event)
    {
        if ($event->hasTask()) {
            return;
        }

        // FIXME: Refactor to tagged services and factory registry.
        $config = $event->getMetaData();

        switch ($config->get(Task::SETTING_TYPE)) {
            case 'install':
                $event->setTask(new InstallTask($config));
                return;
            case 'upgrade':
                $event->setTask(new UpgradeTask($config));
                return;
            case 'require-package':
                if (!$config->has(RequirePackageTask::SETTING_HOME)) {
                    $config->set(RequirePackageTask::SETTING_HOME, $this->home->homeDir());
                }
                $event->setTask(new RequirePackageTask($config));
                return;
            case 'remove-package':
                if (!$config->has(RemovePackageTask::SETTING_HOME)) {
                    $config->set(RemovePackageTask::SETTING_HOME, $this->home->homeDir());
                }
                $event->setTask(new RemovePackageTask($config));
                return;
            default:
        }
    }
}
