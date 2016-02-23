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

namespace Tenside\Core\Events;

use Symfony\Component\EventDispatcher\Event;
use Tenside\Core\Task\Task;
use Tenside\Core\Util\JsonArray;

/**
 * This event is triggered when a task shall be created.
 */
class CreateTaskEvent extends Event
{
    /**
     * The meta data for the task.
     *
     * @var JsonArray
     */
    private $metaData;

    /**
     * The created task.
     *
     * @var Task
     */
    private $task;

    /**
     * Create a new instance.
     *
     * @param JsonArray $metaData The meta data.
     */
    public function __construct(JsonArray $metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * Retrieve meta data.
     *
     * @return JsonArray
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Retrieve task.
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set task.
     *
     * @param Task $task The new value.
     *
     * @return CreateTaskEvent
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Check if a task has been set.
     *
     * @return bool
     */
    public function hasTask()
    {
        return (bool) $this->task;
    }
}
