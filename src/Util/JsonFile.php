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

namespace Tenside\Core\Util;

/**
 * Generic path following json file handler.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class JsonFile extends JsonArray
{
    /**
     * The filename.
     *
     * @var string
     */
    private $filename;

    /**
     * The name of the backup file to create when saving.
     *
     * @var null|string
     */
    private $backupFile;

    /**
     * Create a new instance.
     *
     * @param string      $filename   The filename.
     *
     * @param null|string $backupFile The name of a backup file to create (if none shall be created, pass null).
     *                                The default name of the backup file is the filename with a tilde (~) appended.
     *
     * @throws \RuntimeException When the file contents are invalid.
     */
    public function __construct($filename, $backupFile = '')
    {
        $this->filename = (string) $filename;

        if ('' === $backupFile) {
            $backupFile = $filename . '~';
        }

        $this->backupFile = $backupFile;

        if (file_exists($this->filename)) {
            try {
                parent::__construct(file_get_contents($this->filename));
            } catch (\Exception $exception) {
                throw new \RuntimeException('Error: json file ' . $this->filename . ' is invalid.', 1, $exception);
            }

            return;
        }

        parent::__construct();
    }

    /**
     * Set a value.
     *
     * @param string $path  The path of the value.
     *
     * @param mixed  $value The value to set.
     *
     * @return JsonFile
     */
    public function set($path, $value)
    {
        parent::set($path, $value);

        $this->save();

        return $this;
    }

    /**
     * Retrieve the file name.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Copy the file contents over to the backup.
     *
     * @return void
     */
    private function makeBackup()
    {
        if ((null === $this->backupFile) || !file_exists($this->filename)) {
            return;
        }

        if (!is_dir(dirname($this->backupFile))) {
            mkdir(dirname($this->backupFile), 0700, true);
        }

        copy($this->filename, $this->backupFile);
    }

    /**
     * Save the file data.
     *
     * @return JsonFile
     */
    public function save()
    {
        $this->makeBackup();

        if (!is_dir(dirname($this->filename))) {
            mkdir(dirname($this->filename), 0700, true);
        }

        file_put_contents($this->filename, (string) $this);

        return $this;
    }
}
