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
 * This class tests that the file owner matches the user executing php.
 */
class SelfTestFileOwnerMatches extends AbstractSelfTest
{
    /**
     * Check that we are being called via HTTPS in favor of HTTP.
     *
     * @return void
     */
    protected function doTest()
    {
        $this->setMessage('Check that the file owner matches the user executing php');

        $request = Request::createFromGlobals();

        $owning  = fileowner($request->server->get('SCRIPT_FILENAME'));
        $running = getmyuid();

        if ($owning === $running) {
            $this->markSuccess();

            return;
        }

        $this->markWarning('Script is owned by uid ' . $owning . ' whereas it is being executed by uid ' . $running);
    }
}
