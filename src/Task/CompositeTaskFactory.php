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
 * This class is a composite factory of several other task factories.
 *
 * When queried, it will try all contained factories and return as soon as any provides an result.
 */
class CompositeTaskFactory implements TaskFactoryInterface
{
    /**
     * The registered factories.
     *
     * @var TaskFactoryInterface[]
     */
    private $factories = [];

    /**
     * Create an instance containing the passed factories.
     *
     * @param TaskFactoryInterface[] $factories The factories to add.
     */
    public function __construct(array $factories = [])
    {
        foreach ($factories as $factory) {
            $this->add($factory);
        }
    }

    /**
     * Add the passed factory.
     *
     * @param TaskFactoryInterface $factory The factory to add.
     *
     * @return CompositeTaskFactory
     */
    public function add(TaskFactoryInterface $factory)
    {
        $this->factories[] = $factory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported($taskType)
    {
        return ($this->getFactoryForType($taskType) instanceof TaskFactoryInterface);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException For unsupported task types.
     */
    public function createInstance($taskType, JsonArray $metaData)
    {
        if (!($factory = $this->getFactoryForType($taskType)) instanceof TaskFactoryInterface) {

            throw new \InvalidArgumentException('Do not know how to create task.');
        }

        return $factory->createInstance($taskType, $metaData);
    }

    /**
     * Search the factory that can handle the type.
     *
     * @param string $taskType The task type to search.
     *
     * @return null|TaskFactoryInterface
     */
    private function getFactoryForType($taskType)
    {
        foreach ($this->factories as $factory) {
            if ($factory->isTypeSupported($taskType)) {
                return $factory;
            }
        }

        return null;
    }
}
