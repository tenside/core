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

namespace Tenside\SelfTest\Generic;

use Symfony\Component\HttpFoundation\Request;
use Tenside\SelfTest\AbstractSelfTest;

/**
 * This class tests that we are in secure environment.
 */
class SelfTestCalledViaHttps extends AbstractSelfTest
{
    /**
     * Check that we are being called via HTTPS in favor of HTTP.
     *
     * @return void
     */
    protected function doTest()
    {
        $this->setMessage('Check that HTTPS is used instead of HTTP.');

        $request = Request::createFromGlobals();

        if ($request->isSecure()) {
            $this->markSuccess();

            return;
        }

        $this->markWarning('HTTPS is not used.');
    }
}
