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

namespace Tenside\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Process\Process;
use Tenside\Task\Task;
use Tenside\Util\JsonArray;

/**
 * Lists and executes queued tasks.
 */
class TaskRunnerController extends AbstractController
{
    /**
     * Retrieve the task list.
     *
     * @return JsonResponse
     */
    public function getTasksAction()
    {
        $result = [];
        $list   = $this->getTensideTasks();
        foreach ($list->getIds() as $taskId) {
            $result[$taskId] = [
                'id'   => $taskId,
                'type' => $list->getTask($taskId)->getType()
            ];
        }

        return JsonResponse::create($result);
    }

    /**
     * Retrieve the given task task.
     *
     * @param string  $taskId  The id of the task to retrieve.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     */
    public function getTaskAction($taskId, Request $request)
    {
        // Retrieve the status file of the task.
        $task   = $this->getTensideTasks()->getTask($taskId);
        $offset = null;
        if ($request->query->has('offset')) {
            $offset = (int) $request->query->get('offset');
        }

        return JsonResponse::create(
            [
                'status' => $task->getStatus(),
                'output' => [$task->getOutput($offset)]
            ]
        );
    }

    /**
     * Queue an task to the list.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     *
     * @throws NotAcceptableHttpException When the payload is invalid.
     */
    public function addTaskAction(Request $request)
    {
        $metaData = null;
        $content  = $request->getContent();
        if (empty($content)) {
            throw new NotAcceptableHttpException('Invalid payload');
        }
        $metaData = new JsonArray($content);
        if (!$metaData->has('type')) {
            throw new NotAcceptableHttpException('Invalid payload');
        }

        $taskId = $this->getTensideTasks()->queue($metaData->get('type'), $metaData);

        return JsonResponse::create(
            [
                'status' => 'OK',
                'task'   => $taskId
            ],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Starts the next pending task if any.
     *
     * @return JsonResponse
     */
    public function runAction()
    {
        // FIXME: we need a way to ensure that no other task is running for the moment to prevent race conditions.

        // Fetch the next queued task.
        $task = $this->getTensideTasks()->dequeue();

        if (!$task) {
            return JsonResponse::create(
                [
                    'status' => 'OK',
                    'task'   => null
                ]
            );
        }

        // FIXME: need some way to call back when running inline. Must be done from UI though.

        // Now spawn a runner.
        $this->spawn($task);

        return JsonResponse::create(
            [
                'status' => 'OK',
                'task'   => $task->getId()
            ]
        );
    }

    /**
     * Spawn a detached process for a task.
     *
     * @param Task $task The task to spawn a process for.
     *
     * @return void
     *
     * @throws \RuntimeException When the task could not be started.
     */
    private function spawn(Task $task)
    {
        $phpCli = 'php';
        $config = $this->getTensideConfig();
        // If defined, override the php-cli interpreter.
        if ($config->has('php-cli')) {
            $phpCli = $config->get('php-cli');
        }

        // If defined, add the php-cli interpreter arguments.
        if ($config->has('php-cli-arguments')) {
            $arguments = [];
            foreach ($config->get('php-cli-arguments') as $argument) {
                $arguments[] = $argument;
            }
            $arguments = implode(' ', array_map('escapeshellarg', $arguments));
        }

        $cmd = sprintf(
            '%s %s tenside:runtask %s',
            escapeshellcmd($phpCli),
            isset($arguments) ? $arguments : '',
            escapeshellarg($this->get('tenside.cli_script')->cliExecutable()),
            escapeshellarg($task->getId())
        );

        $commandline = new Process($cmd, $this->get('tenside.home')->homeDir(), null, null, null);

        $commandline->start();
        if (!$commandline->isRunning()) {
            // We might end up here when the process has been forked.
            // If exit code is neither 0 nor null, we have a problem here.
            if ($exitCode = $commandline->getExitCode()) {
                $logger = $this->get('logger');
                $logger->error('Failed to execute "' . $cmd . '"');
                $logger->error('Exit code: ' . $commandline->getExitCode());
                $logger->error('Output: ' . $commandline->getOutput());
                $logger->error('Error output: ' . $commandline->getErrorOutput());
                throw new \RuntimeException(
                    sprintf(
                        'Spawning process task %s resulted in exit code %s',
                        $task->getId(),
                        $exitCode
                    )
                );
            }
        }
    }
}
