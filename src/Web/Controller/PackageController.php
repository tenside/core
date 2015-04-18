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

use Composer\Package\PackageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Composer\PackageConverter;
use Tenside\Util\JsonArray;
use Tenside\Web\UserInformation;

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
        static::createRoute($routes, 'packageList', $endpoint, __CLASS__, ['GET']);
        static::createRoute(
            $routes,
            'getPackage',
            $endpoint . '/{vendor}/{package}',
            __CLASS__,
            ['GET'],
            ['vendor' => '[\-\_a-zA-Z]*', 'package' => '[\-\_a-zA-Z]*']
        );
        static::createRoute(
            $routes,
            'putPackage',
            $endpoint . '/{vendor}/{package}',
            __CLASS__,
            ['PUT'],
            ['vendor' => '[\-\_a-zA-Z]*', 'package' => '[\-\_a-zA-Z]*']
        );
        static::createRoute(
            $routes,
            'deletePackage',
            $endpoint . '/{vendor}/{package}',
            __CLASS__,
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
        $composer  = $this->getTenside()->getComposer();
        $converter = new PackageConverter($composer->getPackage());
        $packages  = $converter->convertRepositoryToArray(
            $composer->getRepositoryManager()->getLocalRepository(),
            !$request->query->has('all')
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
     * Retrieve a package.
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
     * Retrieve the needed role.
     *
     * @return string
     */
    protected function getRole()
    {
        return UserInformation::ROLE_ADMIN;
    }
}
