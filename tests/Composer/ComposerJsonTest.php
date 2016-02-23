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

namespace Tenside\Core\Test\Composer;

use Composer\Package\CompletePackage;
use Tenside\Core\Composer\ComposerJson;
use Tenside\Core\Test\TestCase;

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

    /**
     * Test that the locking of packages works.
     *
     * @return void
     */
    public function testLocking()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "~1.0"
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->lockPackage($package));
        $this->assertTrue($composer->isLocked('vendor/package'));

        $this->assertEquals('~1.0', $composer->get('extra/tenside/version-locks/vendor\/package'));
        $this->assertEquals('1.0.0.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testLockingDependency()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->lockPackage($package));
        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertEquals(false, $composer->get('extra/tenside/version-locks/vendor\/package'));
        $this->assertEquals('1.0.0.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the locking of already locked packages works.
     *
     * @return void
     */
    public function testLockingOfAlreadyLockedKeepsLock()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": "~1.0"
      }
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->lockPackage($package));
        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertEquals('~1.0', $composer->get('extra/tenside/version-locks/vendor\/package'));
        $this->assertEquals('1.0.0.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testLockingOfAlreadyLockedDependencyKeepsLock()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": false
      }
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->lockPackage($package));
        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertEquals(false, $composer->get('extra/tenside/version-locks/vendor\/package'));
        $this->assertEquals('1.0.0.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the unlocking of packages works.
     *
     * @return void
     */
    public function testUnlocking()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": "~1.0"
      }
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->unlockPackage($package));
        $this->assertFalse($composer->isLocked('vendor/package'));

        $this->assertFalse($composer->has('extra/tenside'));
        $this->assertEquals('~1.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testUnlockingDependency()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": false
      }
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->unlockPackage($package));
        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertFalse($composer->has('extra/tenside'));
        $this->assertFalse($composer->has('require/vendor\/package'));
    }

    /**
     * Test that the locking of already locked packages works.
     *
     * @return void
     */
    public function testUnlockingOfAlreadyUnLockedKeepsLockRemoved()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "~1.0"
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->unlockPackage($package));
        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertFalse($composer->has('extra/tenside'));
        $this->assertEquals('~1.0', $composer->get('require/vendor\/package'));
    }

    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testUnlockingOfAlreadyUnlockedDependencyKeepsLockRemoved()
    {
        $content = <<<EOF
{
  "name": "tenside/core"
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->unlockPackage($package));
        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertFalse($composer->has('extra/tenside'));
        $this->assertFalse($composer->has('require/vendor\/package'));
    }

    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testUnlockingDependencyPreservesOtherKeys()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": false
      },
      "yet-another-key": false,
      "even-more": false
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->unlockPackage($package));
        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertFalse($composer->has('require/vendor\/package'));
        $this->assertTrue($composer->has('extra/tenside/yet-another-key'));
        $this->assertTrue($composer->has('extra/tenside/even-more'));
    }

    /**
     * Test that a call to testLock with true calls lockPackage()
     *
     * @return void
     */
    public function testSetLockCallsLockPackage()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "~1.0"
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertFalse($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->setLock($package, true));
        $this->assertTrue($composer->isLocked('vendor/package'));
    }

    /**
     * Test that a call to testLock with false calls unlockPackage()
     *
     * @return void
     */
    public function testSetLockCallsUnlockPackage()
    {
        $content = <<<EOF
{
  "name": "tenside/core",
  "require": {
    "vendor/package": "1.0.0.0"
  },
  "extra": {
    "tenside": {
      "version-locks": {
        "vendor/package": "~1.0"
      }
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));
        $package  = new CompletePackage('vendor/package', '1.0.0.0', '1.0.0.0');

        $this->assertTrue($composer->isLocked('vendor/package'));
        $this->assertSame($composer, $composer->setLock($package, false));
        $this->assertFalse($composer->isLocked('vendor/package'));
    }


    /**
     * Test that the locking of dependency packages works.
     *
     * @return void
     */
    public function testCleanArray()
    {
        $content = <<<EOF
{
  "extra": {
    "tenside": {
      "version-locks": {
      },
      "yet-another-key": false,
      "even-more": false
    }
  }
}
EOF;

        $composer = new ComposerJson($this->createFixture('composer.json', $content));

        $reflection = new \ReflectionMethod($composer, 'cleanEmptyArraysInPath');
        $reflection->setAccessible(true);

        $reflection->invoke($composer, 'extra/tenside');

        $this->assertFalse($composer->has('extra/tenside/version-locks'));
        $this->assertTrue($composer->has('extra/tenside/yet-another-key'));
        $this->assertTrue($composer->has('extra/tenside/even-more'));
    }
}
