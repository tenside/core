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
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\Composer;

use Composer\Package\CompletePackageInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryInterface;
use Tenside\Core\Util\JsonArray;

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
     * Create a new instance and return it.
     *
     * @param RootPackageInterface $rootPackage The root package of the installation.
     *
     * @return PackageConverter
     */
    public static function create(RootPackageInterface $rootPackage)
    {
        return new static($rootPackage);
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
    public static function convertPackageVersion(PackageInterface $package, $fullReference = false)
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
     * @param PackageInterface      $package The package to convert.
     *
     * @param null|PackageInterface $upgrade The package to show as upgradable to.
     *
     * @return JsonArray
     */
    public function convertPackageToArray(PackageInterface $package, PackageInterface $upgrade = null)
    {
        $name = $package->getPrettyName();
        $data = new JsonArray([
            'name'       => $name,
            'version'    => $this->convertPackageVersion($package),
            'constraint' => $this->getConstraint($name),
            'type'       => $package->getType(),
            'locked'     => $this->isLocked($name),
        ]);

        if (null !== ($releaseDate = $package->getReleaseDate())) {
            $data->set('time', $releaseDate->format(\DateTime::ATOM));
        }

        if (null !== $upgrade) {
            $data->set('upgrade_version', $upgrade->getPrettyVersion());
            if (null !== ($upgradeReleaseDate = $upgrade->getReleaseDate())) {
                $data->set('upgrade_time', $upgradeReleaseDate->format(\DateTime::ATOM));
            }
        }

        if ($package instanceof CompletePackageInterface) {
            $this->convertCompletePackage($package, $data);
        }

        return $data;
    }

    /**
     * Convert the information of all packages in a repository to an array used by json API.
     *
     * @param RepositoryInterface      $repository   The repository holding the packages to convert.
     *
     * @param bool                     $requiredOnly If true, return only the packages added to the root package as
     *                                               require.
     *
     * @param null|RepositoryInterface $upgradeList  The packages available as upgrades.
     *
     * @return JsonArray
     */
    public function convertRepositoryToArray(
        RepositoryInterface $repository,
        $requiredOnly = false,
        RepositoryInterface $upgradeList = null
    ) {
        $requires = $requiredOnly ? $this->rootPackage->getRequires() : false;
        $packages = new JsonArray();
        /** @var \Composer\Package\PackageInterface $package */
        foreach ($repository->getPackages() as $package) {
            $name = $package->getPrettyName();
            $esc  = $packages->escape($name);
            if (false === $requires || (isset($requires[$name]))) {
                $upgradePkg = null;
                if ($upgradeList) {
                    $upgradePkg = $upgradeList->findPackage($name, '*');
                }
                $packages->set(
                    $esc,
                    $this->convertPackageToArray($package, $upgradePkg)->getData()
                );
            }
        }

        return $packages;
    }

    /**
     * Check if a package is locked.
     *
     * @param string $packageName The name of the package to test.
     *
     * @return bool
     *
     * @see ComposerJson::isLocked()
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
            return $link->getConstraint()->getPrettyString();
        }

        foreach ($requires as $link) {
            /** @var Link $link */
            if ($link->getTarget() == $packageName) {
                return $link->getConstraint()->getPrettyString();
            }
        }

        return null;
    }

    /**
     * Convert the data of a complete package to the passed json array.
     *
     * @param CompletePackageInterface $package The package to process.
     *
     * @param JsonArray                $data    The json array to push the data to.
     *
     * @return void
     */
    private function convertCompletePackage(CompletePackageInterface $package, $data)
    {
        $data->set('description', $package->getDescription());
        $data->set('license', $package->getLicense());
        if ($keywords = $package->getKeywords()) {
            $data->set('keywords', $keywords);
        }
        if ($homepage = $package->getHomepage()) {
            $data->set('homepage', $homepage);
        }
        if ($authors = $package->getAuthors()) {
            $data->set('authors', $authors);
        }
        if ($support = $package->getSupport()) {
            $data->set('support', $support);
        }
        if ($extra = $package->getExtra()) {
            $data->set('extra', $extra);
        }
        $data->set('abandoned', $package->isAbandoned());
        if ($package->isAbandoned()) {
            $data->set('replacement', $package->getReplacementPackage());
        }
    }
}
