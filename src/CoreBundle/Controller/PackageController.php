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

namespace Tenside\CoreBundle\Controller;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     */
    public function packageListAction(Request $request)
    {
        $composer  = $this->getComposer();
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
        foreach ($packages->getEntries('/') as $packageName) {
            $packages->set($packageName . '/installed', $packages->get($packageName . '/version'));
        }

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
     */
    public function getPackageAction($vendor, $package)
    {
        $packageName = $vendor . '/' . $package;

        $composer  = $this->getComposer();
        $converter = new PackageConverter($composer->getPackage());

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ($package->getPrettyName() === $packageName) {
                return new JsonResponse($converter->convertPackageToArray($package), 200);
            }
        }

        return new Response('Not found', Response::HTTP_NOT_FOUND);
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
     * @throws NotAcceptableHttpException When the passed payload is invalid.
     * @throws NotFoundHttpException When the package has not been found.
     */
    public function putPackageAction($vendor, $package, Request $request)
    {
        $packageName = $vendor . '/' . $package;
        $info        = new JsonArray($request->getContent());
        $name        = $info->get('name');

        if (!($info->has('name') && $info->has('locked') && $info->has('constraint'))) {
            throw new NotAcceptableHttpException('Invalid package information.');
        }

        if ($name !== $packageName) {
            throw new NotAcceptableHttpException('Invalid package information.');
        }

        $composer = $this->getComposer();
        $json     = $this->get('tenside.composer_json');

        $package = $this->findPackage($name, $composer->getRepositoryManager()->getLocalRepository());

        if (null === $package) {
            throw new NotFoundHttpException('Package not found.');
        }

        $json->setLock($package, $info->get('locked'));
        return $this->forward('TensideCoreBundle:Package:getPackage');
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
     * Perform a complete package version solving and return the available upgrade versions.
     *
     * @return JsonArray
     */
    private function fullSolvePass()
    {
        $composer  = $this->getComposer();
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
        $composer     = $this->getComposer();
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
