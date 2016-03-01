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

namespace Tenside\Core\Config;

/**
 * Main tenside configuration (abstraction over tenside.json).
 */
class TensideJsonConfig extends SourceJson
{
    /**
     * Create a new instance.
     *
     * @param string $directory The directory where the tenside.json shall be placed.
     */
    public function __construct($directory)
    {
        parent::__construct($directory . DIRECTORY_SEPARATOR . 'tenside.json');
    }

    /**
     * Retrieve the secret.
     *
     * @return string|null
     */
    public function getSecret()
    {
        return $this->has('secret') ? (string) $this->get('secret') : null;
    }

    /**
     * Retrieve the domain.
     *
     * @return string|null
     */
    public function getLocalDomain()
    {
        return $this->has('domain') ? (string) $this->get('domain') : null;
    }
}
