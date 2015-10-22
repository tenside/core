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

namespace Tenside\SelfTest\Php;

use Tenside\SelfTest\AbstractSelfTest;

/**
 * This class is the abstract base for performing checks that the current environment is suitable for running tenside.
 */
class SelfTestAllowUrlFopenEnabled extends AbstractSelfTest
{
    /**
     * Check that allow url fopen is allowed as it is needed by composer.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if allow_url_fopen is enabled.');

        if (ini_get('allow_url_fopen')) {
            $this->markSuccess();

            return;
        }

        $this->markFailed(
            'The php setting allow_url_fopen must be enabled to allow downloading of data. ' .
            'Note that this does NOT imply any security risk as this is NOT the setting allow_url_include ' .
            '(which should be disabled).'
        );
    }
}
