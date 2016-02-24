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

namespace Tenside\Core\Task;

use Tenside\Core\Util\JsonArray;

/**
 * Interface TaskFactoryInterface
 */
interface TaskFactoryInterface
{
    /**
     * Check if the factory supports the passed task type.
     *
     * @param string $taskType The task type name.
     *
     * @return bool
     */
    public function isTypeSupported($taskType);

    /**
     * Create an instance of the passed type with the given configuration.
     *
     * @param string    $taskType The task type name.
     *
     * @param JsonArray $metaData The meta data for the task.
     *
     * @return Task
     *
     * @throws \InvalidArgumentException For unsupported task types.
     */
    public function createInstance($taskType, JsonArray $metaData);
}
