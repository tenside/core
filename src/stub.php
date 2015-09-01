#!/usr/bin/env php
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

// Avoid APC causing random fatal errors per https://github.com/composer/composer/issues/264
if (extension_loaded('apc') && ini_get('apc.enable_cli') && ini_get('apc.cache_by_default')) {
    if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
        ini_set('apc.cache_by_default', 0);
    } else {
        fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running composer commands.' . PHP_EOL);
        fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.' . PHP_EOL);
    }
}

$fail = false;
if (version_compare(phpversion(), 'TENSIDE_MIN_PHP_VERSION', '<=')) {
    fwrite(
        STDERR,
        'Error: Tenside needs at least PHP @@TENSIDE_MIN_PHP_VERSION@@ while you have ' . phpversion() . PHP_EOL
    );
    $fail = true;
}

if (!extension_loaded('Phar')) {
    fwrite(STDERR, 'Error: Phar extension is needed.' . PHP_EOL);
    $fail = true;
}

// FIXME: I suspect this will not work in web when being called from within phar... :(
$suhosin = ini_get('suhosin.executor.include.whitelist');
if ($suhosin !== false) {
    $allowed = array_map('trim', explode(',', $suhosin));

    if (!(in_array('phar', $allowed) || in_array('phar://', $allowed))) {
        fwrite(STDERR, 'Suhosin disables phar files.' . PHP_EOL);
    }
}

if (!function_exists('curl_init')) {
    fwrite(STDERR, 'Warning: curl_init is not available - expect problems when downloading.' . PHP_EOL);
    $fail = true;
}

Phar::mapPhar('tenside.phar');

// Compiler generated warning time:
// @@TENSIDE_DEV_WARNING_TIME@@

if (PHP_SAPI == 'cli') {
    require 'phar://tenside.phar/bin/tenside';
} else {
    // clean the shebang which might have been written by php-fcgi.
    ob_clean();
    require 'phar://tenside.phar/web/app.php';
}

__HALT_COMPILER();
