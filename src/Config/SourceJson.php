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

use Tenside\Core\Util\JsonArray;

/**
 * JSON based config.
 */
class SourceJson implements SourceInterface
{
    /**
     * The JsonFile.
     *
     * @var JsonArray
     */
    protected $data;

    /**
     * Create a new instance.
     *
     * @param JsonArray $data The json array.
     */
    public function __construct(JsonArray $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $forceArray = false)
    {
        return $this->data->get($path, $forceArray);
    }

    /**
     * {@inheritDoc}
     */
    public function has($path)
    {
        return $this->data->has($path);
    }

    /**
     * {@inheritDoc}
     */
    public function set($path, $value)
    {
        $this->data->set($path, $value);

        return $this;
    }
}
