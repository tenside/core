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

namespace Tenside\Test\Composer;

use Tenside\Composer\ComposerJson;
use Tenside\Test\TestCase;

/**
 * This class tests the composer json handling.
 */
class ComposerJsonTest extends TestCase
{
    /**
     * Test all is* methods.
     *
     * @return void
     */
    public function testMethods()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "require-vendor/require-package": "1.0"
  },
  "require-dev": {
    "require-dev-vendor/require-dev-package": "2.0"
  },
  "replace": {
    "replace-vendor/replace-package": "3.0"
  },
  "provide": {
    "provide-vendor/provide-package": "4.0"
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));

        $this->assertSame($composer, $composer->requirePackage('require-vendor/require-package-2', '1.1'));
        $this->assertSame($composer, $composer->requirePackageDev('require-dev-vendor/require-dev-package-2', '2.1'));
        $this->assertSame($composer, $composer->replacePackage('replace-vendor/replace-package-2', '3.1'));
        $this->assertSame($composer, $composer->providePackage('provide-vendor/provide-package-2', '4.1'));

        $this->assertTrue($composer->isRequiring('require-vendor/require-package'));
        $this->assertTrue($composer->isRequiringDev('require-dev-vendor/require-dev-package'));
        $this->assertTrue($composer->isReplacing('replace-vendor/replace-package'));
        $this->assertTrue($composer->isProviding('provide-vendor/provide-package'));

        $this->assertTrue($composer->isRequiring('require-vendor/require-package-2'));
        $this->assertTrue($composer->isRequiringDev('require-dev-vendor/require-dev-package-2'));
        $this->assertTrue($composer->isReplacing('replace-vendor/replace-package-2'));
        $this->assertTrue($composer->isProviding('provide-vendor/provide-package-2'));

        $this->assertFalse($composer->isRequiring('require-vendor/require-package-not'));
        $this->assertFalse($composer->isRequiringDev('require-dev-vendor/require-dev-package-not'));
        $this->assertFalse($composer->isReplacing('replace-vendor/replace-package-not'));
        $this->assertFalse($composer->isProviding('provide-vendor/provide-package-not'));

        $this->assertEquals('1.0', $composer->getRequire('require-vendor/require-package'));
        $this->assertEquals('2.0', $composer->getRequireDev('require-dev-vendor/require-dev-package'));
        $this->assertEquals('3.0', $composer->getReplace('replace-vendor/replace-package'));
        $this->assertEquals('4.0', $composer->getProvide('provide-vendor/provide-package'));

        $this->assertEquals('1.1', $composer->getRequire('require-vendor/require-package-2'));
        $this->assertEquals('2.1', $composer->getRequireDev('require-dev-vendor/require-dev-package-2'));
        $this->assertEquals('3.1', $composer->getReplace('replace-vendor/replace-package-2'));
        $this->assertEquals('4.1', $composer->getProvide('provide-vendor/provide-package-2'));

        $this->assertNull($composer->getRequire('require-vendor/require-package-not'));
        $this->assertNull($composer->getRequireDev('require-dev-vendor/require-dev-package-not'));
        $this->assertNull($composer->getReplace('replace-vendor/replace-package-not'));
        $this->assertNull($composer->getProvide('provide-vendor/provide-package-not'));
    }
}
