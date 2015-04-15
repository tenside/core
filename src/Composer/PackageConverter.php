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

namespace Tenside\Composer;

use Composer\Package\CompletePackageInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryInterface;

/**
 * The main entry point.
 */
class PackageConverter
{
    /**
     * The root package of the installation.
     *
     * @var RootPackageInterface
     */
    private $rootPackage;

    /**
     * Create a new instance.
     *
     * @param RootPackageInterface $rootPackage The root package of the installation.
     */
    public function __construct(RootPackageInterface $rootPackage)
    {
        $this->rootPackage = $rootPackage;
    }

    /**
     * Convert a package to array information used by json API.
     *
     * @param PackageInterface $package The package to convert.
     *
     * @return array
     */
    public function convertPackageToArray(PackageInterface $package)
    {
        $name = $package->getPrettyName();
        $data = [
            'name' => $name,
            'version' => $package->getPrettyVersion(),
            'constraint' => $this->getConstraint($name),
            'type' => $package->getType(),
            'upgrade_version' => 'FIXME: detect latest version',
            'locked' => $this->isLocked($name)
        ];
        if ($package instanceof CompletePackageInterface) {
            $data['description'] = $package->getDescription();
        }

        return $data;
    }

    /**
     * Convert the information of all packages in a repository to an array used by json API.
     *
     * @param RepositoryInterface $repository   The repository holding the packages to convert.
     *
     * @param bool                $requiredOnly If true, return only the packages added to the root package as require.
     *
     * @return array
     */
    public function convertRepositoryToArray(RepositoryInterface $repository, $requiredOnly = false)
    {
        $requires = $requiredOnly ? $this->rootPackage->getRequires() : false;
        $packages = [];
        /** @var \Composer\Package\PackageInterface $package */
        foreach ($repository->getPackages() as $package) {
            if (false === $requires || (isset($requires[$package->getPrettyName()]))) {
                $packages[$package->getPrettyName()] = $this->convertPackageToArray($package);
            }
        }

        usort($packages, array($this, 'packageCompare'));

        return array_values($packages);
    }

    /**
     * Compare two packages by their names.
     *
     * @param array $left  The first package for comparison.
     *
     * @param array $right The second package for comparison.
     *
     * @return int
     *
     * @internal
     */
    public function packageCompare($left, $right)
    {
        return strnatcasecmp($left['name'], $right['name']);
    }

    /**
     * Check if a package is locked.
     *
     * @param string $packageName The name of the package to test.
     *
     * @return bool
     */
    private function isLocked($packageName)
    {
        $extra = $this->rootPackage->getExtra();
        return isset($extra['contao']['version-locks'][$packageName]);
    }

    /**
     * Determine the constraint defined for a given package (if required via root project).
     *
     * @param string $packageName The name of the package to retrieve the constraint for.
     *
     * @return string|null
     */
    private function getConstraint($packageName)
    {
        $requires = $this->rootPackage->getRequires();
        if (isset($requires[$packageName])) {
            /** @var Link $link */
            $link = $requires[$packageName];
            return $link->getConstraint();
        }

        foreach ($requires as $link) {
            /** @var Link $link */
            if ($link->getTarget() == $packageName) {
                return $link->getConstraint();
            }
        }

        return null;
    }
}
