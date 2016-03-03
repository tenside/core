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

namespace Tenside\Core\SelfTest\Php;

use Tenside\Core\SelfTest\AbstractSelfTest;

/**
 * This class tests the current environment for correctly configured suhosin extension is suitable for running tenside.
 */
class SelfTestSuhosin extends AbstractSelfTest
{
    /**
     * Check that Suhosin is correctly configured.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if Suhosin is either not loaded or correctly configured.');

        if (!$this->isSuhosinLoaded()) {
            $this->markSuccess();
            return;
        }

        $this->markFailed(
            'The php setting allow_url_fopen must be enabled to allow downloading of data. ' .
            'Note that this does NOT imply any security risk as this is NOT the setting allow_url_include ' .
            '(which should be disabled).'
        );
    }

    /**
     * Check if Suhosin is loaded.
     *
     * @return bool
     */
    private function isSuhosinLoaded()
    {
        return extension_loaded('suhosin');
    }
}
