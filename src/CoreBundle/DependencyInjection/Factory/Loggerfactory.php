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

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This class creates a logger instance.
 */
class Loggerfactory
{
    /**
     * Create the logger service.
     *
     * @param Kernel   $kernel         The kernel to retrieve the log dir from.
     *
     * @param string   $filename       The filename.
     *
     * @param int      $maxFiles       The maximal amount of files to keep (0 means unlimited).
     *
     * @param int      $level          The minimum logging level at which this handler will be triggered.
     *
     * @param bool     $bubble         Whether the messages that are handled can bubble up the stack or not.
     *
     * @param int|null $filePermission Optional file permissions (default (0644) are only for owner read/write).
     *
     * @param bool     $useLocking     Try to lock log file before doing any writes.
     *
     * @return RotatingFileHandler
     */
    public static function create(
        $kernel,
        $filename,
        $maxFiles = 0,
        $level = Logger::DEBUG,
        $bubble = true,
        $filePermission = null,
        $useLocking = false
    ) {
        return new RotatingFileHandler(
            $kernel->getLogDir() . DIRECTORY_SEPARATOR . $filename,
            $maxFiles,
            $level,
            $bubble,
            $filePermission,
            $useLocking
        );
    }
}
