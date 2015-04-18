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

namespace Tenside\Test\Composer;

use Composer\Package\RootPackage;
use Tenside\Composer\PackageConverter;

/**
 * Test the PackageConverter.
 *
 * @author Christian Schiffler <https://github.com/discordier>
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
