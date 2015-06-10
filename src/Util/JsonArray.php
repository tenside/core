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
class JsonArray implements \JsonSerializable
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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Load the data.
     *
     * @param string $data The json data.
     *
     * @return JsonArray
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

        return $this;
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
        return array_map(array($this, 'unescape'), preg_split('#(?<!\\\)\/#', $path));
    }

    /**
     * Escape a string to be used as literal path.
     *
     * @param string $path The string to escape.
     *
     * @return string
     */
    public function unescape($path)
    {
        return str_replace('\/', '/', $path);
    }

    /**
     * Escape a string to be used as literal path.
     *
     * @param string $path The string to escape.
     *
     * @return string
     */
    public function escape($path)
    {
        return str_replace('/', '\/', $path);
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
        // special case, root element.
        if ($path === '/') {
            return $this->data;
        }

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
     * @return JsonArray
     */
    public function set($path, $value)
    {
        // special case, root element.
        if ($path === '/') {
            $this->data = $value;
            return $this;
        }

        $chunks = $this->splitPath($path);
        $scope  = &$this->data;
        $count  = count($chunks);

        if (empty($chunks)) {
            return $this;
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

            return $this;
        }

        $scope[$sub] = $value;

        return $this;
    }

    /**
     * Check if a value exists.
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

    /**
     * Unset a value.
     *
     * @param string $path The path of the value.
     *
     * @return JsonArray
     */
    public function remove($path)
    {
        return $this->set($path, null);
    }

    /**
     * Check if a given path has an empty value (or does not exist).
     *
     * @param string $path The sub path to be sorted.
     *
     * @return bool
     */
    public function isEmpty($path)
    {
        return (null === ($value = $this->get($path))) || empty($value);
    }

    /**
     * Sort the array by the provided user function.
     *
     * @param callable $callback The callback function to use.
     *
     * @param string   $path     The sub path to be sorted.
     *
     * @return void
     */
    public function uasort($callback, $path = '/')
    {
        $value = $this->get($path);
        if (null === $value || !is_array($value)) {
            return;
        }

        uasort($value, $callback);

        $this->set($path, $value);
    }

    /**
     * Encode the array as string and return it.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Return the data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return (object) $this->data;
    }
}
