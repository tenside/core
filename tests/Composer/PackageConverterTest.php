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
 * @author     Nico Schneider <nico.tcap@gmail.com>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Test\Composer;

use Composer\Package\RootPackage;
use Tenside\Composer\PackageConverter;

/**
 * Test the PackageConverter.
 */
class PackageConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that an empty array does not return values.
     *
     * @return void
     */
    public function testEmpty()
    {
        $package   = new RootPackage('test/package', '0.1.1.1', '0.1.1.1');
        $converter = new PackageConverter($package);

        $converted = $converter->convertPackageToArray($package);

        $this->assertEquals($package->getName(), $converted->get('name'));
    }
}
