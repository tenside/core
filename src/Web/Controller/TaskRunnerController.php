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

namespace Tenside\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Task\Task;
use Tenside\Util\JsonArray;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Lists and executes queued tasks.
 */
class TaskRunnerController extends AbstractRestrictedController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'getTasks', '/api/v1/tasks');
        static::createRoute($routes, 'run', '/api/v1/tasks/run'); // FIXME: unsure about this one not really an API call like the others.
        static::createRoute($routes, 'getTask', '/api/v1/tasks/{taskId}', ['GET'], ['taskId' => '[a-z0-9]+']);
        static::createRoute($routes, 'addTask', '/api/v1/tasks', ['POST']);

        static::createRoute($routes, 'runInline', '/api/v1/run-task/{taskId}', ['GET'], ['taskId' => '[a-z0-9]+']);
    }

    /**
     * Retrieve the task list.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     */
    protected function getTasksAction(Request $request)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_UPGRADE);

        $result = [];
        $list   = $this->getTaskList();
        foreach ($list->getIds() as $taskId) {
            $result[$taskId] = [
                'id'   => $taskId,
                'type' => $list->getTask($taskId)->getType()
            ];
        }

        return new JsonResponse(
            $result
        );
    }

    /**
     * Retrieve the given task task.
     *
     * @param string $taskId The id of the task to retrieve.
     *
     * @return JsonResponse
     */
    protected function getTaskAction($taskId)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_UPGRADE);

        // Retrieve the status file of the task.
        $task = $this->getTenside()->getTaskList()->getTask($taskId);

        return new JsonResponse(
            [
                'status' => $task->getStatus(),
                'output' => [$task->getOutput()]
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
    protected function addTaskAction(Request $request)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_UPGRADE);

        $metaData = null;
        $content  = $request->getContent();
        if (empty($content)) {
            throw new NotAcceptableHttpException('Invalid payload');
        }
        $metaData = new JsonArray($content);
        if (!$metaData->has('type')) {
            throw new NotAcceptableHttpException('Invalid payload');
        }

        $taskId = $this->getTaskList()->queue($metaData->get('type'), $metaData);

        return new JsonResponse(
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
    protected function runAction()
    {
        $this->needAccessLevel(UserInformationInterface::ACL_UPGRADE);

        // FIXME: we need a way to ensure that no other task is running for the moment to prevent race conditions.

        // Fetch the next queued task.
        $task = $this->getTaskList()->dequeue();


        if (!$task) {
            return new JsonResponse(
                [
                    'status' => 'OK',
                    'id'     => null
                ]
            );
        }

        // FIXME: need some way to call back when running inline. Must be done from UI though.

        // Now spawn a runner.
        $this->spawn($task);

        return new JsonResponse(
            [
                'status' => 'OK',
                'id'     => $task->getId()
            ]
        );
    }

    /**
     * Get the task list instance.
     *
     * @return \Tenside\Task\TaskList
     */
    private function getTaskList()
    {
        return $this->getTenside()->getTaskList();
    }

    /**
     * Spawn a detached process for a task.
     *
     * @param Task $task The task to spawn a process for.
     *
     * @return void
     */
    private function spawn(Task $task)
    {
        $phpCli = 'php';
        $config = $this->getTenside()->getConfigSource();
        if ($config->has('php-cli')) {
            $phpCli = $config->get('php-cli');
        }

        $cmd = sprintf(
            '%s %s %s',
            escapeshellcmd($phpCli),
            escapeshellarg($this->getTenside()->getCliExecutable()),
            escapeshellarg($task->getId())
        );

        $commandline = new Process($cmd);
        $commandline->start();
        $commandline->getPid();
    }
}
