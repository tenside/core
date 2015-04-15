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

namespace Tenside\Util;

/**
 * Generic path following json file handler.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class JsonArray
{
    /**
     * The json data.
     *
     * @var array
     */
    private $data;

    /**
     * Create a new instance.
     *
     * @param string|array $data The json data.
     */
    public function __construct($data = '{}')
    {
        if (is_string($data)) {
            $this->load($data);
        }
        if (is_array($data)) {
            $this->setData($data);
        }
    }

    /**
     * Set the data.
     *
     * @param array $data The data array.
     *
     * @return JsonArray
     */
    public function setData($data)
    {
        $this->data = (array) $data;

        return $this;
    }

    /**
     * Merge the passed data into this instance.
     *
     * @param array $data The data to absorb.
     *
     * @return JsonArray
     */
    public function merge($data)
    {
        return $this->setData(array_replace_recursive($this->getData(), (array) $data));
    }

    /**
     * Retrieve the data as json string.
     *
     * @return string
     */
    public function getData()
    {
        return json_encode($this->data, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Load the data.
     *
     * @param string $data The json data.
     *
     * @return void
     *
     * @throws \RuntimeException When the data is invalid.
     */
    public function load($data)
    {
        $data = json_decode($data, true);
        if ($data === null) {
            throw new \RuntimeException('Error: json data is invalid. ' . json_last_error_msg(), 1);
        }
        $this->setData($data);
    }

    /**
     * Split the path into chunks.
     *
     * @param string $path The path to split.
     *
     * @return array
     */
    protected function splitPath($path)
    {
        return preg_split('#(?<!\\\)\/#', $path);
    }

    /**
     * Retrieve a value.
     *
     * @param string $path       The path of the value.
     *
     * @param bool   $forceArray Flag if the result shall be casted to array.
     *
     * @return array|null
     */
    public function get($path, $forceArray = false)
    {
        $chunks = $this->splitPath($path);
        $scope  = $this->data;

        if (empty($chunks)) {
            return null;
        }

        while (null !== ($sub = array_shift($chunks))) {
            if (isset($scope[$sub])) {
                if ($forceArray) {
                    $scope = (array) $scope[$sub];
                } else {
                    $scope = $scope[$sub];
                }
            } else {
                if ($forceArray) {
                    return array();
                } else {
                    return null;
                }
            }
        }
        return $scope;
    }

    /**
     * Set a value.
     *
     * @param string $path  The path of the value.
     *
     * @param mixed  $value The value to set.
     *
     * @return void
     */
    public function set($path, $value)
    {
        $chunks = $this->splitPath($path);
        $scope  = &$this->data;
        $count  = count($chunks);

        if (empty($chunks)) {
            return;
        }

        while ($count > 1) {
            $sub   = array_shift($chunks);
            $count = count($chunks);

            if ((!(isset($scope[$sub]) && is_array($scope[$sub])))) {
                $scope[$sub] = array();
            }

            $scope = &$scope[$sub];
        }

        $sub = $chunks[0];

        if ($value === null) {
            unset($scope[$sub]);

            return;
        }

        $scope[$sub] = $value;
    }

    /**
     * Retrieve a value.
     *
     * @param string $path The path of the value.
     *
     * @return bool
     */
    public function has($path)
    {
        $chunks = $this->splitPath($path);
        $scope  = $this->data;

        if (empty($chunks)) {
            return null;
        }

        while (null !== ($sub = array_shift($chunks))) {
            if (isset($scope[$sub])) {
                $scope = $scope[$sub];
            } else {
                return false;
            }
        }

        return true;
    }
}
