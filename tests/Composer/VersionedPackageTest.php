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

use Composer\Package\PackageInterface;
use Tenside\Composer\Package\VersionedPackage;
use Tenside\Test\TestCase;

/**
 * This tests the VersionedPackage class.
 */
class VersionedPackageTest extends TestCase
{
    /**
     * Provide the test values for testAllMethodsAreDelegated()
     *
     * @return array
     */
    public function methodDelegateProvider()
    {
        return [
            ['getName'],
            ['getPrettyName'],
            ['getNames'],
            ['setId', ['id']],
            ['getId'],
            ['isDev'],
            ['getType'],
            ['getTargetDir'],
            ['getExtra'],
            ['setInstallationSource', ['dist']],
            ['getInstallationSource'],
            ['getSourceType'],
            ['getSourceUrl'],
            ['getSourceUrls'],
            ['getSourceReference'],
            ['getSourceMirrors'],
            ['getDistType'],
            ['getDistUrl'],
            ['getDistUrls'],
            ['getDistReference'],
            ['getDistSha1Checksum'],
            ['getDistMirrors'],
            ['getVersion'],
            ['getPrettyVersion'],
            ['getFullPrettyVersion', [true]],
            ['getReleaseDate'],
            ['getStability'],
            ['getRequires'],
            ['getConflicts'],
            ['getProvides'],
            ['getReplaces'],
            ['getDevRequires'],
            ['getSuggests'],
            ['getAutoload'],
            ['getDevAutoload'],
            ['getIncludePaths'],
            ['setRepository', [$this->getMockForAbstractClass('Composer\Repository\RepositoryInterface')]],
            ['getRepository'],
            ['getBinaries'],
            ['getUniqueName'],
            ['getNotificationUrl'],
            ['__toString'],
            ['getPrettyString'],
            ['getArchiveExcludes'],
            ['getTransportOptions'],
        ];
    }

    /**
     * Test that the class delegates all method calls to the real package.
     *
     * @param string $method    The method to call.
     *
     * @param array  $arguments The arguments to pass.
     *
     * @return void
     *
     * @dataProvider methodDelegateProvider
     */
    public function testAllMethodsAreDelegated($method, $arguments = [])
    {
        $package = $this->getMockForAbstractClass('Composer\Package\PackageInterface');
        $package->expects($this->once())->method($method);

        /** @var PackageInterface $package */
        $versioned = new VersionedPackage($package);

        call_user_func_array([$versioned, $method], $arguments);
    }

    /**
     * Mock a package version.
     *
     * @param string $versionString The version string.
     *
     * @param string $releaseDate   The release date.
     *
     * @return PackageInterface
     */
    private function mockVersion($versionString, $releaseDate)
    {
        $version = $this->getMockForAbstractClass('Composer\Package\PackageInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject $version */
        $version->method('getVersion')->willReturn($versionString);
        $version->method('getReleaseDate')->willReturn(new \DateTime($releaseDate));

        return $version;
    }

    /**
     * Test that manipulation of meta data works correctly.
     *
     * @return void
     */
    public function testMetaData()
    {
        $versioned = new VersionedPackage($this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'));

        $this->assertEquals(null, $versioned->getMetaData('test'));
        $this->assertEquals($versioned, $versioned->addMetaData('test', 'initial value', false));
        $this->assertEquals('initial value', $versioned->getMetaData('test'));
        $this->assertEquals($versioned, $versioned->addMetaData('test', 'will not override', false));
        $this->assertEquals('initial value', $versioned->getMetaData('test'));
        $this->assertEquals($versioned, $versioned->addMetaData('test', 'will override', true));
        $this->assertEquals('will override', $versioned->getMetaData('test'));
        $this->assertEquals($versioned, $versioned->replaceMetaData(['foo' => 'bar']));
        $this->assertEquals(null, $versioned->getMetaData('test'));
        $this->assertEquals('bar', $versioned->getMetaData('foo'));
    }

    /**
     * Test the version handling methods.
     *
     * @return void
     */
    public function testVersionHandling()
    {
        $versioned           = new VersionedPackage(
            $this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'),
            [$initialVersion = $this->mockVersion('1.0.0.0', '2000-01-01 00:00:00')]
        );

        $this->assertEquals([$initialVersion], $versions = $versioned->getVersions());

        $moreVersions = [
            $this->mockVersion('1.1.0.0', '2010-01-01 00:00:00'),
            $this->mockVersion('1.1.5.0', '2012-04-15 10:00:00'),
            $this->mockVersion('1.2.0.0', '2015-01-01 10:00:00'),
        ];

        $this->assertEquals($versioned, $versioned->addVersions($moreVersions));
        $this->assertEquals(array_merge($versions, $moreVersions), $versioned->getVersions());

        $this->assertEquals('1.2.0.0', $versioned->getLatestVersion()->getVersion());
        $this->assertEquals($versioned, $versioned->removeVersion($initialVersion));

        $this->assertEquals($versioned, $versioned->setVersions([$initialVersion]));
        $this->assertEquals([$initialVersion], $versioned->getVersions());
    }

    /**
     * Test removing of a version by the version string.
     *
     * @return void
     */
    public function testRemovalByString()
    {
        $versions = [
            $this->mockVersion('1.0.0.0', '2000-01-01 00:00:00'),
            $this->mockVersion('1.1.0.0', '2010-01-01 00:00:00'),
            $this->mockVersion('1.1.5.0', '2012-04-15 10:00:00'),
            $this->mockVersion('1.2.0.0', '2015-01-01 10:00:00'),
        ];

        $versioned = new VersionedPackage($this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'), $versions);

        array_shift($versions);

        $this->assertEquals($versioned, $versioned->removeVersion('1.0.0.0'));
        $this->assertEquals($versions, array_values($versioned->getVersions()));
    }

    /**
     * Test removing of a version by the version string.
     *
     * @return void
     */
    public function testRemovalByVersionInstance()
    {
        $versions = [
            $this->mockVersion('1.0.0.0', '2000-01-01 00:00:00'),
            $this->mockVersion('1.1.0.0', '2010-01-01 00:00:00'),
            $this->mockVersion('1.1.5.0', '2012-04-15 10:00:00'),
            $this->mockVersion('1.2.0.0', '2015-01-01 10:00:00'),
        ];

        $versioned = new VersionedPackage($this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'), $versions);

        $this->assertEquals($versioned, $versioned->removeVersion(array_shift($versions)));
        $this->assertEquals($versions, array_values($versioned->getVersions()));
    }

    /**
     * Test that calling getLatestVersion on a package without versions returns the package itself.
     *
     * @return void
     */
    public function testGetLatestVersionForNoVersionsReturnsPackage()
    {
        $versioned = new VersionedPackage($package = $this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'));
        $this->assertEquals($package, $versioned->getLatestVersion());
    }

    /**
     * Test that calling getLatestVersion on a package with older alternative versions returns the package itself.
     *
     * @return void
     */
    public function testGetLatestVersionForOlderVersionsReturnsPackage()
    {
        $versioned   = new VersionedPackage(
            $package = $this->mockVersion('10.0.0.0', '2030-01-01 00:00:00'),
            [
                $this->mockVersion('1.0.0.0', '2000-01-01 00:00:00'),
                $this->mockVersion('1.1.0.0', '2010-01-01 00:00:00'),
                $this->mockVersion('1.1.5.0', '2012-04-15 10:00:00'),
                $this->mockVersion('1.2.0.0', '2015-01-01 10:00:00'),
            ]
        );
        $this->assertEquals($package, $versioned->getLatestVersion());
    }

    /**
     * Test that removal of invalid arguments will raise an exception.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testRemovalOfInvalidVersionRaisesException()
    {
        $versioned = new VersionedPackage($this->mockVersion('0.1.0.0', '2000-01-01 00:00:00'));

        $versioned->removeVersion(15);
    }
}
