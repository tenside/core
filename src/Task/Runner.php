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

/**
 * This class runs a task.
 */
class Runner
{
    /**
     * The task to be run.
     *
     * @var Task
     */
    private $task;

    /**
     * Create a new instance.
     *
     * @param Task $task The task to be run.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Run the task.
     *
     * @param string $logfile The log file to use.
     *
     * @return bool
     */
    public function run($logfile)
    {
        return Task::STATE_FINISHED === $this->task->perform($logfile);
    }
}
