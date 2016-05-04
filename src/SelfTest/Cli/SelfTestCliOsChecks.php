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

namespace Tenside\Core\SelfTest\Cli;

use Tenside\Core\SelfTest\AbstractSelfTest;

/**
 * This class tests for any OS specific hacks that shall get enabled.
 */
class SelfTestCliOsChecks extends AbstractSelfTest
{
    /**
     * Perform all checks.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if any OS specific workaround should be enabled.');

        $flagged = $this->checkDarwin();
        if (!$flagged) {
            $this->markSuccess('Good, no hacks in place');
        }
    }

    /**
     * Check if any Darwin specific hacks must get enabled.
     *
     * @return bool True if modified, false otherwise.
     */
    private function checkDarwin()
    {
        if ('Darwin' !== PHP_OS) {
            return false;
        }

        // Apply hack to force process into background as it keeps sticky on Darwin.
        // Dunno why but as I lack the hardware and knowledge of said OS, this hack will have to do.
        $this->getAutoConfig()->setForceToBackground(true);
        $this->markSuccess(
            'Mac OS X (Darwin) detected, force-to-background-workaround enabled.'
        );

        return true;
    }
}
