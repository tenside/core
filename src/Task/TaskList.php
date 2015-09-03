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

use Tenside\Util\JsonArray;
use Tenside\Util\JsonFile;

/**
 * This class is an implementation of the task list.
 */
class TaskList
{
    /**
     * The config to read from.
     *
     * @var string
     */
    private $dataDir;

    /**
     * Create a new instance.
     *
     * @param string $dataDir The directory to keep the database in.
     */
    public function __construct($dataDir)
    {
        $this->dataDir = $dataDir;
    }

    /**
     * Add the task to the list.
     *
     * @param string         $type     The type name.
     *
     * @param JsonArray|null $metaData The (optional) meta data.
     *
     * @return string
     */
    public function queue($type, JsonArray $metaData = null)
    {
        $taskId = $this->generateId();

        if (null === $metaData) {
            $metaData = new JsonArray();
        }

        $metaData
            ->set('id', $taskId)
            ->set('type', $type);

        $this->getConfig()->set($taskId, $metaData->getData());

        $taskFile = new JsonFile($this->taskIdToFileName($taskId));
        $taskFile->setData($metaData->getData());
        $taskFile->save();

        $this->createTaskFromMetaData($taskFile);

        return $taskId;
    }

    /**
     * Remove a task from the queue (or the first one if no id given).
     *
     * @param null|string $taskId The id of the task to dequeue, if null the first queued task will be returned.
     *
     * @return Task|null
     */
    public function dequeue($taskId = null)
    {
        $idList = $this->getIds();
        if ((null === $taskId) && (false === ($taskId = current($idList)))) {
            return null;
        }

        if (!in_array($taskId, $idList)) {
            // Not in list, get out.
            return null;
        }

        $this->getConfig()->remove($taskId);

        return $this->getTask($taskId);
    }

    /**
     * Retrieve the ids of all registered tasks.
     *
     * @return string[]
     */
    public function getIds()
    {
        return  $this->getConfig()->getEntries('/');
    }

    /**
     * Retrieve a task.
     *
     * @param string $taskId The id of the task to retrieve.
     *
     * @return Task|null
     */
    public function getTask($taskId)
    {
        $filename = $this->taskIdToFileName($taskId);
        if (!file_exists($filename)) {
            return null;
        }

        return $this->createTaskFromMetaData(new JsonFile($filename));
    }

    /**
     * Retrieve the correct filename for the given task id.
     *
     * @param string $taskId The task id to generate the file name for.
     *
     * @return string
     */
    private function taskIdToFileName($taskId)
    {
        return $this->dataDir . DIRECTORY_SEPARATOR . 'tenside-task-' . $taskId . '.json';
    }

    /**
     * Create a task instance from the given MetaData.
     *
     * @param JsonArray $config The configuration of the task.
     *
     * @return Task|null
     */
    private function createTaskFromMetaData(JsonArray $config)
    {

        switch ($config->get('type')) {
            case 'upgrade':
                return new UpgradeTask($config);
            default:
        }

        return null;
    }

    /**
     * Generate a task id.
     *
     * @return string
     */
    private function generateId()
    {
        return md5(uniqid('', true));
    }

    /**
     * Get the config file instance.
     *
     * @return JsonFile
     */
    private function getConfig()
    {
        return new JsonFile($this->dataDir . DIRECTORY_SEPARATOR . 'tenside-tasks.json');
    }
}
