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

namespace Tenside\CoreBundle\Controller;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tenside\Composer\PackageConverter;
use Tenside\Composer\SolverRunner;
use Tenside\Util\JsonArray;

/**
 * List and manipulate the installed packages.
 */
class PackageController extends AbstractController
{
    /**
     * Retrieve the package list.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     *
     * @api
     */
    public function packageListAction(Request $request)
    {
        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());
        if ($request->query->has('solve')) {
            $upgrades = $this->fullSolvePass();
        } else {
            $upgrades = $this->quickSolvePass();
        }

        $packages = $converter->convertRepositoryToArray(
            $composer->getRepositoryManager()->getLocalRepository(),
            !$request->query->has('all'),
            $upgrades
        );

        return new JsonResponse($packages->getData(), 200);
    }

    /**
     * Retrieve a package.
     *
     * @param string $vendor  The name of the vendor.
     *
     * @param string $package The name of the package.
     *
     * @return JsonResponse
     *
     * @api
     */
    public function getPackageAction($vendor, $package)
    {
        $packageName = $vendor . '/' . $package;

        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            /** @var PackageInterface $package */
            if ($package->getPrettyName() === $packageName) {
                return new JsonResponse($converter->convertPackageToArray($package), 200);
            }
        }

        return new Response('Not found', 404);
    }

    /**
     * Update the information of a package in the composer.json.
     *
     * @param string  $vendor  The name of the vendor.
     *
     * @param string  $package The name of the package.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     *
     * @api
     */
    public function putPackageAction($vendor, $package, Request $request)
    {
        $packageName = $vendor . '/' . $package;
        $info        = new JsonArray($request->getContent());

        if ($info->get('name') !== $packageName) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());

        try {
            $new = $converter->updatePackageFromArray(
                $info,
                $composer->getRepositoryManager()->getLocalRepository(),
                $this->get('tenside.composer_json')
            );
            return new JsonResponse($new, 200);
        } catch (\InvalidArgumentException $exception) {
            return new Response('Not found', 404);
        }
    }

    /**
     * Remove a package.
     *
     * @param string $vendor  The name of the vendor.
     *
     * @param string $package The name of the package.
     *
     * @return JsonResponse
     *
     * @api
     */
    public function deletePackageAction($vendor, $package)
    {
        $packageName = $vendor . '/' . $package;

        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            /** @var PackageInterface $package */
            if ($package->getPrettyName() === $packageName) {
                return new JsonResponse($converter->convertPackageToArray($package), 200);
            }
        }

        return new Response('Not found', 404);
    }

    /**
     * Perform a complete package version solving and return the available upgrade versions.
     *
     * @return JsonArray
     */
    private function fullSolvePass()
    {
        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());
        $upgrades  = new JsonArray();
        $solver    = new SolverRunner($composer);
        $jobs      = $solver->solve();
        foreach ($jobs as $job) {
            if ($job instanceof UpdateOperation) {
                $upgrades->set(
                    $upgrades->escape($job->getInitialPackage()->getPrettyName()),
                    $converter->convertPackageVersion($job->getTargetPackage())
                );
            }
        }

        return $upgrades;
    }

    /**
     * Perform a quick resolving on the packages.
     *
     * @return JsonArray
     */
    private function quickSolvePass()
    {
        // FIXME: implement quick solving here.
        $composer     = $this->getTenside()->getComposer();
        $upgrades     = new JsonArray();
        $manager      = $composer->getRepositoryManager();
        $local        = $manager->getLocalRepository();
        $installed    = new CompositeRepository([$local, new PlatformRepository()]);
        $repositories = new CompositeRepository(
            array_merge([$installed], $manager->getRepositories())
        );
        // FIXME: build a list of all constraints here.

        /** @var PackageInterface $package */
        foreach ($local->getPackages() as $package) {
            /** @var PackageInterface[] $versions */
            $versions = $repositories->findPackages($package->getName());
            /** @var PackageInterface $latest */
            $latest = false;
            if (count($versions)) {
                foreach ($versions as $version) {
                    // FIXME: check if the constraint matches against the constraint in the list.
                    if (!$latest || ($version->getReleaseDate() > $latest->getReleaseDate())) {
                        $latest = $version;
                    }
                }
            }

            if ($latest) {
                $upgrades->set($upgrades->escape($latest->getName()), $latest->getPrettyVersion());
            }
        }

        return $upgrades;
    }
}
