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

namespace Tenside\Util;

/**
 * This class provides methods to check if certain functions have been disabled in PHP or are callable.
 */
class FunctionAvailabilityCheck
{
    /**
     * Cache.
     *
     * @var string[]
     */
    private static $blackListSuhosin;

    /**
     * Cache.
     *
     * @var string[]
     */
    private static $blackListPhpIni;


    /**
     * Check if function is defined.
     *
     * @param string $function  The function to test.
     *
     * @param string $extension The optional name of an php extension providing said function.
     *
     * @return bool
     */
    public static function isFunctionEnabled($function, $extension = null)
    {
        return
            (null === $extension || extension_loaded($extension))
            && !static::isFunctionBlacklistedInPhpIni($function)
            && !static::isFunctionBlacklistedInSuhosin($function)
            && static::isFunctionDefined($function);
    }

    /**
     * Check if function is defined.
     *
     * @param string $function The function to test.
     *
     * @return bool
     */
    public static function isFunctionDefined($function)
    {
        return function_exists($function);
    }

    /**
     * Check if function is blacklisted in Suhosin.
     *
     * @param string $function The function to test.
     *
     * @return bool
     */
    public static function isFunctionBlacklistedInSuhosin($function)
    {
        if (!extension_loaded('suhosin')) {
            return false;
        }

        if (!isset(static::$blackListSuhosin)) {
            static::$blackListSuhosin = static::prepareList(ini_get('suhosin.executor.func.blacklist'));
        }

        return static::isFunctionsMentionedInList($function, static::$blackListSuhosin);
    }

    /**
     * Check if method is blacklisted in Suhosin.
     *
     * @param string $function The function to test.
     *
     * @return bool
     */
    public static function isFunctionBlacklistedInPhpIni($function)
    {
        if (!isset(static::$blackListPhpIni)) {
            static::$blackListPhpIni = static::prepareList(ini_get('disable_functions'));
        }

        return static::isFunctionsMentionedInList($function, static::$blackListPhpIni);
    }

    /**
     * Check if a function is mentioned in the passed (comma separated) list.
     *
     * @param string   $function The function to test.
     *
     * @param string[] $list     The function list.
     *
     * @return bool
     */
    public static function isFunctionsMentionedInList($function, $list)
    {
        if (empty($list)) {
            return false;
        }

        return (false !== array_search($function, $list));
    }

    /**
     * Explode a list.
     *
     * @param string $list The list.
     *
     * @return string[]
     */
    private static function prepareList($list)
    {
        return array_map('strtolower', array_map('trim', explode(',', $list, -1)));
    }
}
