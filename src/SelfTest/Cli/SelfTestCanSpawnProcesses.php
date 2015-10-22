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

namespace Tenside\SelfTest\Cli;

use Tenside\SelfTest\AbstractSelfTest;
use Tenside\Util\FunctionAvailabilityCheck;

/**
 * This class tests that proc_open is available.
 */
class SelfTestCanSpawnProcesses extends AbstractSelfTest
{
    /**
     * Check that we have a correct CLI executable of PHP.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if PHP may spawn processes.');

        if (!FunctionAvailabilityCheck::isFunctionDefined('proc_open')) {
            $this->markFailed(
                'The process execution relies on proc_open, which is not available on your PHP installation.'
            );

            return;
        }

        if (FunctionAvailabilityCheck::isFunctionBlacklistedInSuhosin('proc_open')) {
            $this->markFailed(
                'The process execution relies on proc_open, which is disabled in Suhosin on your PHP installation. ' .
                'Please remove "proc_open" from "suhosin.executor.func.blacklist" in php.ini'
            );

            return;
        }

        if (FunctionAvailabilityCheck::isFunctionBlacklistedInPhpIni('proc_open')) {
            $this->markFailed(
                'The process execution relies on proc_open, which is disabled in your PHP installation. ' .
                'Please remove "proc_open" from "disabled_functions" in php.ini'
            );

            return;
        }

        $this->markSuccess();
    }
}
