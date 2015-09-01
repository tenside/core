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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

/**
 * Redirecting output to send the output data to the task json file.
 */
class TaskOutput extends Output
{
    /**
     * The task for which this output is attached to..
     *
     * @var Task
     */
    private $task;

    /**
     * Create a new instance.
     *
     * @param Task                          $task      The task being logged to.
     *
     * @param int                           $verbosity The verbosity level
     *                                                 (one of the VERBOSITY constants in OutputInterface).
     *
     * @param bool                          $decorated Whether to decorate messages.
     *
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     *
     * @api
     */
    public function __construct(
        Task $task,
        $verbosity = self::VERBOSITY_NORMAL,
        $decorated = false,
        OutputFormatterInterface $formatter = null
    ) {
        parent::__construct($verbosity, $decorated, $formatter);

        $this->task = $task;
    }

    /**
     * Fetch buffer content.
     *
     * @return string
     */
    public function fetch()
    {
        return $this->task->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        if ($newline) {
            $message .= "\n";
        }

        $this->task->addOutput($message);
    }
}
