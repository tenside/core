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
use Tenside\Task\InstallTask;
use Tenside\Task\Task;
use Tenside\Task\UpgradeTask;

/**
 * This class provides loading of the tenside core configuration.
 */
class TaskListener
{
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

        $config = $event->getMetaData();

        switch ($config->get(Task::SETTING_TYPE)) {
            case 'install':
                $event->setTask(new InstallTask($config));
                return;
            case 'upgrade':
                $event->setTask(new UpgradeTask($config));
                return;
            default:
        }
    }
}
