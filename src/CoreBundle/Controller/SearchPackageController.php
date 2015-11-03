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
 * @author     Nico Schneider <nico.tcap@gmail.com>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\CoreBundle\Controller;

use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tenside\Composer\Package\VersionedPackage;
use Tenside\Composer\PackageConverter;
use Tenside\Composer\Search\CompositeSearch;
use Tenside\Composer\Search\RepositorySearch;
use Tenside\Util\JsonArray;

/**
 * List and manipulate the installed packages.
 */
class SearchPackageController extends AbstractController
{
    /**
     * Search for packages.
     *
     * @param Request $request The search request.
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $data            = new JsonArray($request->getContent());
        $localRepository = $this->getComposer()->getRepositoryManager()->getLocalRepository();
        $searcher        = $this->getRepositorySearch($data);
        $results         = $searcher->searchAndDecorate($data->get('keywords'));
        $responseData    = [];
        $rootPackage     = $this->getComposer()->getPackage();
        $converter       = new PackageConverter($rootPackage);

        foreach ($results as $versionedResult) {
            /** @var VersionedPackage $versionedResult */

            // Might have no version matching the current stability setting.
            if (null === ($latestVersion = $versionedResult->getLatestVersion())) {
                continue;
            }

            $package = $converter->convertPackageToArray($latestVersion);
            $package
                ->set('installed', $this->getInstalledVersion($localRepository, $versionedResult->getName()))
                ->set('downloads', $versionedResult->getMetaData('downloads'))
                ->set('favers', $versionedResult->getMetaData('favers'));

            $responseData[$package->get('name')] = $package->getData();
        }

        return new JsonResponse($responseData);
    }

    /**
     * Retrieve the installed version of a package (if any).
     *
     * @param RepositoryInterface $localRepository The local repository.
     *
     * @param string              $packageName     The name of the package to search.
     *
     * @return null|string
     */
    private function getInstalledVersion($localRepository, $packageName)
    {
        if (count($installed = $localRepository->findPackages($packageName))) {
            /** @var PackageInterface[] $installed */
            return $installed[0]->getPrettyVersion();
        }

        return null;
    }

    /**
     * Create a repository search instance.
     *
     * @param JsonArray $data The search data.
     *
     * @return CompositeSearch
     */
    private function getRepositorySearch($data)
    {
        $composer          = $this->getComposer();
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository   = $repositoryManager->getLocalRepository();

        $repositories = new CompositeRepository(
            [
                new PlatformRepository(),
                $localRepository,
            ]
        );

        // If we do not search locally, add the other repositories now.
        if ('installed' === $data->get('type')) {
            $repositories->addRepository(new CompositeRepository($repositoryManager->getRepositories()));
        }

        $searcher = new CompositeSearch(
            [
                new RepositorySearch($repositories)
            ]
        );

        return $searcher;
    }
}
