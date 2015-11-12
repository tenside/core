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

use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\RootPackage;
use Composer\Repository\ArrayRepository;
use Composer\Semver\Constraint\Constraint;
use Tenside\Composer\PackageConverter;
use Tenside\Test\TestCase;

/**
 * Test the PackageConverter.
 */
class PackageConverterTest extends TestCase
{
    /**
     * Test the main conversion values.
     *
     * @return void
     */
    public function testEmpty()
    {
        $package = new RootPackage('test/package', '0.1.1.1', '0.1.1.1');
        $package->setType('project');
        $package->setLicense(['LGPL-3']);

        $converted = PackageConverter::create($package)->convertPackageToArray($package);

        $this->assertEquals(
            [
                'name',
                'version',
                'constraint',
                'type',
                'locked',
                'license',
                'abandoned'
            ],
            $converted->getEntries('/')
        );

        $this->assertEquals($package->getName(), $converted->get('name'));
        $this->assertEquals($package->getPrettyVersion(), $converted->get('version'));
        $this->assertNull($converted->get('constraint'));
        $this->assertEquals($package->getType(), $converted->get('type'));
        $this->assertFalse($converted->get('locked'));
        $this->assertEquals($package->getLicense(), $converted->get('license'));
        $this->assertFalse($converted->get('abandoned'));
    }

    /**
     * Test the main conversion values.
     *
     * @return void
     */
    public function testFull()
    {
        $package = new RootPackage('test/package', '0.1.1.1', '0.1.1.1');
        $package->setType('project');
        $package->setLicense(['LGPL-3']);
        $package->setType('project');
        $package->setReleaseDate($time = new \DateTime());
        $package->setDescription('Descriptiontext');
        $package->setKeywords(
            [
                'keyword1',
                'keyword1'
            ]
        );
        $package->setHomepage('https://example.org');
        $package->setAuthors(
            [
                [
                    'name'  => 'A. Coder',
                    'email' => 'a.coder@example.org'
                ],
                [
                    'name'  => 'A. Nother. Coder',
                    'email' => 'a.nother.coder@example.org'
                ],
            ]
        );
        $package->setSupport(
            [
                'issues' => 'https://example.org/issue-tracker'
            ]
        );
        $package->setAbandoned('another/package');

        $converted = PackageConverter::create($package)->convertPackageToArray($package, '1.1.1.1');

        $this->assertEquals(
            [
                'name',
                'version',
                'constraint',
                'type',
                'locked',
                'time',
                'upgrade_version',
                'description',
                'license',
                'keywords',
                'homepage',
                'authors',
                'support',
                'abandoned',
                'replacement'
            ],
            $converted->getEntries('/')
        );

        $this->assertEquals($package->getName(), $converted->get('name'));
        $this->assertEquals($package->getPrettyVersion(), $converted->get('version'));
        $this->assertNull($converted->get('constraint'));
        $this->assertEquals($package->getType(), $converted->get('type'));
        $this->assertFalse($converted->get('locked'));
        $this->assertEquals($package->getLicense(), $converted->get('license'));
        $this->assertEquals($time->format(\DateTime::ATOM), $converted->get('time'));
        $this->assertEquals('1.1.1.1', $converted->get('upgrade_version'));
        $this->assertEquals($package->getDescription(), $converted->get('description'));
        $this->assertEquals($package->getKeywords(), $converted->get('keywords'));
        $this->assertEquals($package->getHomepage(), $converted->get('homepage'));
        $this->assertEquals($package->getAuthors(), $converted->get('authors'));
        $this->assertEquals($package->getSupport(), $converted->get('support'));
        $this->assertEquals($package->isAbandoned(), $converted->get('abandoned'));
        $this->assertEquals($package->getReplacementPackage(), $converted->get('replacement'));
    }

    /**
     * Test that package version conversion bails when neither dist nor source references are specified.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testConvertPackageVersionBailsWithoutReferences()
    {
        $package = new RootPackage('test/package', 'dev-master', 'dev-master');
        PackageConverter::create($package)->convertPackageVersion($package);
    }

    /**
     * Test that package version conversion works from dist references.
     *
     * @return void
     */
    public function testConvertPackageVersionReturnsDistReference()
    {
        $package = new RootPackage('test/package', 'dev-master', 'dev-master');
        $package->setDistType('zip');
        $package->setDistReference('4f934d928260e126b5d06392e12ee20fae258232');
        $package->setDistUrl('https://example.com/4f934d928260e126b5d06392e12ee20fae258232.zip');
        $package->setSourceType('zip');
        $package->setSourceReference('4f934d928260e126b5d06392e12ee20fae258232');
        $package->setSourceUrl('https://example.com/4f934d928260e126b5d06392e12ee20fae258232.zip');

        $converter = PackageConverter::create($package);
        $this->assertEquals(
            'dev-master#4f934d928260e126b5d06392e12ee20fae258232',
            $converter->convertPackageVersion($package, true)
        );
        $this->assertEquals(
            'dev-master#4f934d92',
            $converter->convertPackageVersion($package)
        );
    }

    /**
     * Test that package version conversion works from source references when no dist is present.
     *
     * @return void
     */
    public function testConvertPackageVersionReturnsSourceReferenceWithoutDist()
    {
        $package = new RootPackage('test/package', 'dev-master', 'dev-master');
        $package->setSourceType('zip');
        $package->setSourceReference('4f934d928260e126b5d06392e12ee20fae258232');
        $package->setSourceUrl('https://example.com/4f934d928260e126b5d06392e12ee20fae258232.zip');

        $converter = PackageConverter::create($package);
        $this->assertEquals(
            'dev-master#4f934d928260e126b5d06392e12ee20fae258232',
            $converter->convertPackageVersion($package, true)
        );
        $this->assertEquals(
            'dev-master#4f934d92',
            $converter->convertPackageVersion($package)
        );
    }

    /**
     * Test the conversion of a complete repository.
     *
     * @return void
     */
    public function testConvertRepositoryToArray()
    {
        $repository       = new ArrayRepository(
            [
                $package1 = new CompletePackage('test/dependency1', '1.0.0.0', '1.0.0.0'),
                $package2 = new CompletePackage('test/dependency2', '2.0.0.0', '2.0.0.0'),
            ]
        );
        $package1->setType('project');
        $package1->setLicense(['LGPL-3']);
        $package2->setType('project');
        $package2->setLicense(['GPL-3']);

        $converter = new PackageConverter(new RootPackage('root/package', 'dev-master', 'dev-master'));
        $converted = $converter->convertRepositoryToArray($repository);

        $this->assertEquals(
            [
                'test\/dependency1',
                'test\/dependency2'
            ],
            $converted->getEntries('/')
        );

        $this->assertEquals(
            [
                'test\/dependency1/name',
                'test\/dependency1/version',
                'test\/dependency1/constraint',
                'test\/dependency1/type',
                'test\/dependency1/locked',
                'test\/dependency1/license',
                'test\/dependency1/abandoned',
            ],
            $converted->getEntries('/test\/dependency1')
        );
        $this->assertEquals(
            [
                'test\/dependency2/name',
                'test\/dependency2/version',
                'test\/dependency2/constraint',
                'test\/dependency2/type',
                'test\/dependency2/locked',
                'test\/dependency2/license',
                'test\/dependency2/abandoned'
            ],
            $converted->getEntries('/test\/dependency2')
        );

        $this->assertEquals($package1->getName(), $converted->get('test\/dependency1/name'));
        $this->assertEquals($package1->getPrettyVersion(), $converted->get('test\/dependency1/version'));
        $this->assertNull($converted->get('test\/dependency1/constraint'));
        $this->assertEquals($package1->getType(), $converted->get('test\/dependency1/type'));
        $this->assertFalse($converted->get('test\/dependency1/locked'));
        $this->assertEquals($package1->getLicense(), $converted->get('test\/dependency1/license'));
        $this->assertFalse($converted->get('test\/dependency1/abandoned'));

        $this->assertEquals($package2->getName(), $converted->get('test\/dependency2/name'));
        $this->assertEquals($package2->getPrettyVersion(), $converted->get('test\/dependency2/version'));
        $this->assertNull($converted->get('test\/dependency2/constraint'));
        $this->assertEquals($package2->getType(), $converted->get('test\/dependency2/type'));
        $this->assertFalse($converted->get('test\/dependency2/locked'));
        $this->assertEquals($package2->getLicense(), $converted->get('test\/dependency2/license'));
        $this->assertFalse($converted->get('test\/dependency2/abandoned'));
    }

    /**
     * Test the conversion of a complete repository.
     *
     * @return void
     */
    public function testConvertRepositoryToArrayOnlyRequired()
    {
        $repository       = new ArrayRepository(
            [
                $package1 = new CompletePackage('test/dependency1', '1.0.0.0', '1.0.0.0'),
                $package2 = new CompletePackage('test/non-dependency2', '2.0.0.0', '2.0.0.0'),
            ]
        );
        $package1->setType('project');
        $package1->setLicense(['LGPL-3']);
        $package2->setType('project');
        $package2->setLicense(['GPL-3']);

        $converter = new PackageConverter($rootPackage = new RootPackage('root/package', 'dev-master', 'dev-master'));
        $rootPackage->setRequires([
            'test/dependency1' => new Link('root/package', 'test/dependency1', new Constraint('<', '2.0'))
        ]);

        $converted = $converter->convertRepositoryToArray($repository, true);

        $this->assertEquals(
            [
                'test\/dependency1'
            ],
            $converted->getEntries('/')
        );

        $this->assertEquals(
            [
                'test\/dependency1/name',
                'test\/dependency1/version',
                'test\/dependency1/constraint',
                'test\/dependency1/type',
                'test\/dependency1/locked',
                'test\/dependency1/license',
                'test\/dependency1/abandoned',
            ],
            $converted->getEntries('/test\/dependency1')
        );

        $this->assertEquals($package1->getName(), $converted->get('test\/dependency1/name'));
        $this->assertEquals($package1->getPrettyVersion(), $converted->get('test\/dependency1/version'));
        $this->assertEquals($converted->get('test\/dependency1/constraint'), '< 2.0');
        $this->assertEquals($package1->getType(), $converted->get('test\/dependency1/type'));
        $this->assertFalse($converted->get('test\/dependency1/locked'));
        $this->assertEquals($package1->getLicense(), $converted->get('test\/dependency1/license'));
        $this->assertFalse($converted->get('test\/dependency1/abandoned'));
    }
}
