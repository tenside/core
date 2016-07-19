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
 * This class provides instantiation of composer command tasks.
 */
class AbstractTaskFactory implements TaskFactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * This implementation returns true as soon as a protected method named: "create<TaskTypeName>" exists.
     */
    public function isTypeSupported($taskType)
    {
        return method_exists($this, $this->methodNameFromType($taskType));
    }

    /**
     * {@inheritdoc}
     *
     * This implementation will call the protected method named: "create<TaskTypeName>" if it exists.
     *
     * @throws \InvalidArgumentException For unsupported task types.
     */
    public function createInstance($taskType, JsonArray $metaData)
    {
        $methodName = $this->methodNameFromType($taskType);
        if ($this->isTypeSupported($taskType)) {
            return call_user_func([$this, $methodName], $metaData);
        }

        throw new \InvalidArgumentException('Do not know how to create task ' . $taskType);
    }

    /**
     * Create a method name from a task type name.
     *
     * @param string $type The type to create the method name for.
     *
     * @return string
     */
    protected function methodNameFromType($type)
    {
        return 'create' . implode('', array_map('ucfirst', explode('-', $type)));
    }
}
