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

use Tenside\Tenside;

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
     * The tenside instance.
     *
     * @var Tenside
     */
    private $tenside;

    /**
     * Create a new instance.
     *
     * @param Task    $task    The task to be run.
     *
     * @param Tenside $tenside The tenside instance to use.
     */
    public function __construct(Task $task, Tenside $tenside)
    {
        $this->task    = $task;
        $this->tenside = $tenside;
    }

    /**
     * Run the task.
     *
     * @return void
     */
    public function run()
    {
        $this->task->perform($this->tenside);
    }
}
