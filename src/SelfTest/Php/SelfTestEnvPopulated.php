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
 * This class tests the that the $_ENV gets populated.
 */
class SelfTestEnvPopulated extends AbstractSelfTest
{
    /**
     * Check that Suhosin is correctly configured.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if $_ENV is populated.');

        if (!$this->isConfigured()) {
            $this->markSuccess(
                'The super global $_ENV is not populated. ' .
                'This is default and correct on production servers. ' .
                'However, if your processes do not work in the background, you should enable `E` in the php.ini ' .
                'settings `variables_order` and see if it works. ' .
                'Please report your findings to the developer team.'
            );
            return;
        }

        if (!$this->isPopulated()) {
            $this->markWarning(
                'The super global $_ENV is not populated but should have been ' .
                '("variables_order" in php.ini contains "E").'
            );
            return;
        }

        $this->markSuccess();
    }

    /**
     * Check if php.ini tells to populate.
     *
     * @return bool
     */
    private function isConfigured()
    {
        return (strpos(ini_get('variables_order'), 'E') !== false);
    }

    /**
     * Check if Suhosin is loaded.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function isPopulated()
    {
        $keys = array_keys($_ENV);
        return !empty($keys);
    }
}
