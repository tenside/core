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

use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tenside\Composer\Repository\PackagistRepository;
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
        $data              = new JsonArray($request->getContent());
        $composer          = $this->getTenside()->getComposer();
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository   = $repositoryManager->getLocalRepository();

        $repositories = new CompositeRepository(
            [
                new PackagistRepository(),
                $localRepository,
                new PlatformRepository(),
                new CompositeRepository($repositoryManager->getRepositories())
            ]
        );

        $searcher = new CompositeSearch([
            new RepositorySearch($repositories)
        ]);

        $results = $searcher->search($data->get('keywords'));

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
