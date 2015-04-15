<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Config;

use Tenside\Util\JsonFile;

/**
 * JSON based config.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class SourceJson implements SourceInterface
{
    /**
     * The JsonFile.
     *
     * @var JsonFile
     */
    protected $jsonFile;

    /**
     * Create a new instance.
     *
     * @param string $filename The filename.
     */
    public function __construct($filename)
    {
        $this->jsonFile = new JsonFile($filename);
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $forceArray = false)
    {
        return $this->jsonFile->get($path, $forceArray);
    }

    /**
     * {@inheritDoc}
     */
    public function has($path)
    {
        return $this->jsonFile->has($path);
    }

    /**
     * {@inheritDoc}
     */
    public function set($path, $value)
    {
        $this->jsonFile->set($path, $value);

        return $this;
    }
}
