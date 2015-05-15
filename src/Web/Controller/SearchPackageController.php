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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Composer\Package\VersionedPackage;
use Tenside\Composer\Search\CompositeSearch;
use Tenside\Composer\Search\RepositorySearch;
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

        $data              = new JsonArray($request->getContent());
        $composer          = $this->getTenside()->getComposer();
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository   = $repositoryManager->getLocalRepository();

        $repositories = new CompositeRepository(
            [
                new CompositeRepository($repositoryManager->getRepositories()),
                new PlatformRepository(),
                $localRepository,
            ]
        );

        $searcher = new CompositeSearch(
            [
                new RepositorySearch($repositories)
            ]
        );

        $results = $searcher->searchAndDecorate($data->get('keywords'));

        $responseData = [];

        foreach ($results as $versionedResult) {
            /** @var VersionedPackage $versionedResult */

            if (count($installed = $localRepository->findPackages($versionedResult->getName()))) {
                /** @var PackageInterface[] $installed */
                $installedVersionNumber = $installed[0]->getPrettyVersion();
            }

            $latestVersion = $versionedResult->getLatestVersion();

            $package = [
                'name'        => $latestVersion->getName(),
                'installed'   => isset($installedVersionNumber)
                    ? $installedVersionNumber
                    : null,
                'type'        => $latestVersion->getType(),
                'description' => method_exists($latestVersion, 'getDescription')
                    ? $latestVersion->getDescription()
                    : '',
                'latestVersion' => $latestVersion->getVersion(),
                'downloads' => $versionedResult->getMetaData('downloads'),
                'favers' => $versionedResult->getMetaData('favers'),
            ];

            $responseData[$package['name']] = $package;

        }

        return new JsonResponse($responseData);
    }
}
