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

use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Util\JsonArray;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * List and manipulate the installed packages.
 */
class SearchPackageController extends AbstractRestrictedController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute(
            $routes,
            'search',
            '/api/v1/search',
            ['PUT']
        );
    }

    /**
     * Search for packages.
     *
     * @param Request $request The search request.
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_MANIPULATE_REQUIREMENTS);

        $data                = new JsonArray($request->getContent());
        $composer            = $this->getTenside()->getComposer();
        $repositoryManager   = $composer->getRepositoryManager();
        $platformRepo        = new PlatformRepository();
        $localRepository     = $repositoryManager->getLocalRepository();
        $installedRepository = new CompositeRepository([$localRepository, $platformRepo]);
        $repositories        = new CompositeRepository(
            array_merge([$installedRepository], $repositoryManager->getRepositories())
        );

        $results = $repositories->search(
            $data->get('keywords'),
            RepositoryInterface::SEARCH_FULLTEXT
        );

        $packages = array();
        foreach ($results as $result) {
            if (!isset($packages[$result['name']])) {
                $packages[$result['name']] = $result;

                $packages[$result['name']]['installed'] = null;
                if ($installed = $localRepository->findPackages($result['name'])) {
                    /** @var PackageInterface[] $installed */
                    $packages[$result['name']]['installed'] = $installed[0]->getPrettyVersion();
                }

                /** @var PackageInterface[] $versions */
                $versions = $repositories->findPackages($result['name']);

                /** @var PackageInterface|CompletePackageInterface $latestVersion */
                $latestVersion = false;
                if (count($versions)) {
                    $packages[$result['name']]['type']        = $versions[0]->getType();
                    $packages[$result['name']]['description'] = $versions[0] instanceof CompletePackageInterface
                        ? $versions[0]->getDescription()
                        : '';
                    foreach ($versions as $version) {
                        if (!$latestVersion || $version->getReleaseDate() > $latestVersion->getReleaseDate()) {
                            $latestVersion = $version;
                        }
                    }
                }

                if ($latestVersion) {
                    $packages[$result['name']]['type'] = $latestVersion->getType();

                    if ($latestVersion instanceof CompletePackageInterface) {
                        $packages[$result['name']]['description'] = $latestVersion->getDescription();
                    }
                }
            }
        }

        return new JsonResponse($packages);
    }
}
