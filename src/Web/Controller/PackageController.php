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

namespace Tenside\Web\Controller;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Composer\PackageConverter;
use Tenside\Composer\SolverRunner;
use Tenside\Util\JsonArray;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * List and manipulate the installed packages.
 */
class PackageController extends AbstractRestrictedController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        $endpoint = '/api/v1/packages';
        static::createRoute($routes, 'packageList', $endpoint, ['GET']);
        static::createRoute(
            $routes,
            'getPackage',
            $endpoint . '/{vendor}/{package}',
            ['GET'],
            ['vendor' => '[\-\_a-zA-Z]*', 'package' => '[\-\_a-zA-Z]*']
        );
        static::createRoute(
            $routes,
            'putPackage',
            $endpoint . '/{vendor}/{package}',
            ['PUT'],
            ['vendor' => '[\-\_a-zA-Z]*', 'package' => '[\-\_a-zA-Z]*']
        );
        static::createRoute(
            $routes,
            'deletePackage',
            $endpoint . '/{vendor}/{package}',
            ['DELETE'],
            ['vendor' => '[\-\_a-zA-Z]*', 'package' => '[\-\_a-zA-Z]*']
        );
    }

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
        $this->needAccessLevel(UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS);

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
        $this->needAccessLevel(UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS);

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
        $this->needAccessLevel(UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS);

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
                $this->getTenside()->getComposerJson()
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
        $this->needAccessLevel(UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS);

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
