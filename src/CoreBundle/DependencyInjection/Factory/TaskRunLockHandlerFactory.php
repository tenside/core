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

namespace Tenside\CoreBundle\DependencyInjection\Factory;

use Symfony\Component\Filesystem\LockHandler;
use Tenside\CoreBundle\HomePathDeterminator;

/**
 * This class creates a lock instance to be used when trying to run a task.
 */
class TaskRunLockHandlerFactory
{
    /**
     * Create an instance.
     *
     * @param HomePathDeterminator $home The home determinator.
     *
     * @return LockHandler
     */
    public static function create(HomePathDeterminator $home)
    {
        return new LockHandler('task-run', $home->tensideDataDir());
    }
}
