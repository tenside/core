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

use Composer\Package\AliasPackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryInterface;
use Tenside\Util\JsonArray;

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
     * Convert a package version into string representation.
     *
     * @param PackageInterface $package       The package to extract the version from.
     *
     * @param bool             $fullReference Flag if the complete reference shall be added or an abbreviated form.
     *
     * @return string
     *
     * @throws \RuntimeException If the package is a dev package and does not have valid reference information.
     */
    public function convertPackageVersion(PackageInterface $package, $fullReference = false)
    {
        $version = $package->getPrettyVersion();

        if ('dev' === $package->getStability()) {
            if (null === ($reference = $package->getDistReference())) {
                if (null === ($reference = $package->getSourceReference())) {
                    throw new \RuntimeException('Unable to determine reference for ' . $package->getPrettyName());
                }
            }

            $version .= '#' . (!$fullReference ? substr($reference, 0, 8) : $reference);
        }

        return $version;
    }

    /**
     * Convert a package to array information used by json API.
     *
     * @param PackageInterface $package        The package to convert.
     *
     * @param null|string      $upgradeVersion The package version to show as upgradable to.
     *
     * @return JsonArray
     */
    public function convertPackageToArray(PackageInterface $package, $upgradeVersion = null)
    {
        $name = $package->getPrettyName();
        $data = new JsonArray([
            'name' => $name,
            'version' => $this->convertPackageVersion($package),
            'constraint' => $this->getConstraint($name),
            'type' => $package->getType(),
            'locked' => $this->isLocked($name)
        ]);

        if (null !== $upgradeVersion) {
            $data->set('upgrade_version', $upgradeVersion);
        }

        if ($package instanceof CompletePackageInterface) {
            $data->set('description', $package->getDescription());
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
     * @param JsonArray           $upgradeList  The package version to show as upgradable to.
     *
     * @return JsonArray
     */
    public function convertRepositoryToArray(
        RepositoryInterface $repository,
        $requiredOnly = false,
        JsonArray $upgradeList = null
    ) {
        $requires = $requiredOnly ? $this->rootPackage->getRequires() : false;
        $packages = new JsonArray();
        /** @var \Composer\Package\PackageInterface $package */
        foreach ($repository->getPackages() as $package) {
            $name = $package->getPrettyName();
            $esc  = $packages->escape($name);
            if (false === $requires || (isset($requires[$name]))) {
                $upgradeVersion = null;
                if ($upgradeList && $upgradeList->has($esc)) {
                    $upgradeVersion = $upgradeList->get($esc);
                }
                $packages->set(
                    $esc,
                    $this->convertPackageToArray($package, $upgradeVersion)->getData()
                );
            }
        }

        $packages->uasort([$this, 'packageCompare']);

        return $packages;
    }

    /**
     * Update the extra information in the root package.
     *
     * @param array $extra The extra information.
     *
     * @return void
     */
    private function updateExtra($extra)
    {
        if ($this->rootPackage instanceof Package) {
            $this->rootPackage->setExtra($extra);
        }
    }

    /**
     * Unlock a locked package version.
     *
     * @param PackageInterface $package      The repository holding the packages to convert.
     *
     * @param ComposerJson     $composerJson The composer json to manipulate.
     *
     * @return void
     */
    private function lockPackage(PackageInterface $package, ComposerJson $composerJson)
    {
        $name = $package->getPrettyName();
        $lock = 'extra/tenside/version-locks/' . $composerJson->escape($name);

        // Nothing to do?
        if ($composerJson->has($lock)) {
            return;
        }

        if ($composerJson->isRequiring($name)) {
            $composerJson->set($lock, $composerJson->getRequire($name));
        } else {
            $composerJson->set($lock, false);
        }

        $composerJson->requirePackage($package->getPrettyName(), $this->convertPackageVersion($package, true));
        $this->updateExtra($composerJson->get('extra'));
    }

    /**
     * Unlock a locked package version.
     *
     * @param PackageInterface $package      The repository holding the packages to convert.
     *
     * @param ComposerJson     $composerJson The composer json to manipulate.
     *
     * @return void
     */
    private function unlockPackage(PackageInterface $package, ComposerJson $composerJson)
    {
        $name = $package->getPrettyName();
        $lock = 'extra/tenside/version-locks/' . $composerJson->escape($name);

        // Nothing to do?
        if (!$composerJson->has($lock)) {
            return;
        }

        if (false === ($constraint = $composerJson->get($lock))) {
            $composerJson->remove($lock);
            $composerJson->requirePackage($name, null);
            $this->updateExtra($composerJson->get('extra'));

            return;
        }

        $composerJson->requirePackage($name, $constraint);
        $composerJson->remove($lock);
        if ($composerJson->isEmpty('extra/tenside/version-locks')) {
            $composerJson->remove('extra/tenside/version-locks');
        }
        $this->updateExtra($composerJson->get('extra'));
    }

    /**
     * Search the repository for a package.
     *
     * @param string              $name       The pretty name of the package to search.
     *
     * @param RepositoryInterface $repository The repository to be searched.
     *
     * @return null|PackageInterface
     */
    private function findPackage($name, RepositoryInterface $repository)
    {
        /** @var PackageInterface[] $packages */
        $packages = $repository->findPackages($name);

        while (!empty($packages) && $packages[0] instanceof AliasPackage) {
            array_shift($packages);
        }

        if (empty($packages)) {
            return null;
        }

        return $packages[0];
    }

    /**
     * Update some package from an array.
     *
     * This method only allows changing of the constraint and/or the locking of the package version.
     *
     * @param JsonArray           $array        The package information to update the package from.
     *
     * @param RepositoryInterface $repository   The repository holding the packages to convert.
     *
     * @param ComposerJson        $composerJson The composer json to manipulate.
     *
     * @return JsonArray
     *
     * @throws \InvalidArgumentException When the package information is invalid or the package has not been found.
     */
    public function updatePackageFromArray(
        JsonArray $array,
        RepositoryInterface $repository,
        ComposerJson $composerJson
    ) {
        if (!($array->has('name') && $array->has('locked') && $array->has('constraint'))) {
            throw new \InvalidArgumentException('Invalid package information.');
        }

        $name    = $array->get('name');
        $package = $this->findPackage($name, $repository);

        if (null === $package) {
            throw new \InvalidArgumentException('Package not found.');
        }

        if ($array->get('locked') && !$this->isLocked($name)) {
            $this->lockPackage($package, $composerJson);
        } elseif (!$array->get('locked') && $this->isLocked($name)) {
            $this->unlockPackage($package, $composerJson);
        }

        return $this->convertPackageToArray($package);
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
        return isset($extra['tenside']['version-locks'][$packageName]);
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
