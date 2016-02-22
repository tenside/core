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

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tenside\Composer\Package\VersionedPackage;
use Tenside\Composer\PackageConverter;
use Tenside\Composer\Search\CompositeSearch;
use Tenside\Composer\Search\RepositorySearch;
use Tenside\CoreBundle\Annotation\ApiDescription;
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
     *
     * @ApiDoc(
     *   section="search",
     *   statusCodes = {
     *     200 = "When everything worked out ok"
     *   }
     * )
     * @ApiDescription(
     *   request={
     *    "keywords" = {
     *      "dataType" = "string",
     *      "description" = "The name of the project to search or any other keyword.",
     *      "required" = true
     *    },
     *    "version" = {
     *      "dataType" = "string",
     *      "description" = "The name of the project to install.",
     *      "required" = false
     *    },
     *    "type" = {
     *      "dataType" = "choice",
     *      "description" = "The type of package to search (optional, default: all).",
     *      "format" = "['installed', 'contao', 'all']",
     *      "required" = false
     *    }
     *   },
     *   response={
     *     "package name 1...n" = {
     *       "actualType" = "object",
     *       "subType" = "object",
     *       "description" = "The content of the packages",
     *       "children" = {
     *         "name" = {
     *           "dataType" = "string",
     *           "description" = "The name of the package"
     *         },
     *         "version" = {
     *           "dataType" = "string",
     *           "description" = "The version of the package"
     *         },
     *         "constraint" = {
     *           "dataType" = "string",
     *           "description" = "The constraint of the package (when package is installed)"
     *         },
     *         "type" = {
     *           "dataType" = "string",
     *           "description" = "The noted package type"
     *         },
     *         "locked" = {
     *           "dataType" = "string",
     *           "description" = "Flag if the package has been locked for updates"
     *         },
     *         "time" = {
     *           "dataType" = "datetime",
     *           "description" = "The release date"
     *         },
     *         "upgrade_version" = {
     *           "dataType" = "string",
     *           "description" = "The version available for upgrade (optional, if any)"
     *         },
     *         "description" = {
     *           "dataType" = "string",
     *           "description" = "The package description"
     *         },
     *         "license" = {
     *           "actualType" = "collection",
     *           "subType" = "string",
     *           "description" = "The licenses"
     *         },
     *         "keywords" = {
     *           "actualType" = "collection",
     *           "subType" = "string",
     *           "description" = "The keywords"
     *         },
     *         "homepage" = {
     *           "dataType" = "string",
     *           "description" = "The support website (optional, if any)"
     *         },
     *         "authors" = {
     *           "actualType" = "collection",
     *           "subType" = "object",
     *           "description" = "The authors",
     *           "children" = {
     *             "name" = {
     *               "dataType" = "string",
     *               "description" = "Full name of the author (optional, if any)"
     *             },
     *             "homepage" = {
     *               "dataType" = "string",
     *               "description" = "Email address of the author (optional, if any)"
     *             },
     *             "email" = {
     *               "dataType" = "string",
     *               "description" = "Homepage URL for the author (optional, if any)"
     *             },
     *             "role" = {
     *               "dataType" = "string",
     *               "description" = "Author's role in the project (optional, if any)"
     *             }
     *           }
     *         },
     *         "support" = {
     *           "actualType" = "collection",
     *           "subType" = "object",
     *           "description" = "The support options",
     *           "children" = {
     *             "email" = {
     *               "dataType" = "string",
     *               "description" = "Email address for support (optional, if any)"
     *             },
     *             "issues" = {
     *               "dataType" = "string",
     *               "description" = "URL to the issue tracker (optional, if any)"
     *             },
     *             "forum" = {
     *               "dataType" = "string",
     *               "description" = "URL to the forum (optional, if any)"
     *             },
     *             "wiki" = {
     *               "dataType" = "string",
     *               "description" = "URL to the wiki (optional, if any)"
     *             },
     *             "irc" = {
     *               "dataType" = "string",
     *               "description" = "IRC channel for support, as irc://server/channel (optional, if any)"
     *             },
     *             "source" = {
     *               "dataType" = "string",
     *               "description" = "URL to browse or download the sources (optional, if any)"
     *             },
     *             "docs" = {
     *               "dataType" = "string",
     *               "description" = "URL to the documentation (optional, if any)"
     *             },
     *           }
     *         },
     *         "abandoned" = {
     *           "dataType" = "boolean",
     *           "description" = "Flag if this package is abandoned"
     *         },
     *         "replacement" = {
     *           "dataType" = "string",
     *           "description" = "Replacement for this package (optional, if any)"
     *         },
     *         "installed" = {
     *           "dataType" = "int",
     *           "description" = "Amount of installations"
     *         },
     *         "downloads" = {
     *           "dataType" = "int",
     *           "description" = "Amount of downloads"
     *         },
     *         "favers" = {
     *           "dataType" = "int",
     *           "description" = "Amount of favers"
     *         },
     *       }
     *     }
     *   }
     * )
     */
    public function searchAction(Request $request)
    {
        $composer        = $this->getComposer();
        $data            = new JsonArray($request->getContent());
        $keywords        = $data->get('keywords');
        $type            = $data->get('type');
        $localRepository = $composer->getRepositoryManager()->getLocalRepository();
        $searcher        = $this->getRepositorySearch($keywords, $type, $composer);
        $results         = $searcher->searchAndDecorate($keywords, $this->getFilters($data));
        $responseData    = [];
        $rootPackage     = $composer->getPackage();
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

        return JsonResponse::create($responseData)
            ->setEncodingOptions((JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_FORCE_OBJECT));
    }

    /**
     * Get the array of filter closures.
     *
     * @param string $type The desired search type (contao, installed or empty).
     *
     * @return \Closure[]
     */
    private function getFilters($type)
    {
        $filters = [];
        if ('contao' === $type) {
            $filters[] =
                function ($package) {
                    /** @var PackageInterface $package */
                    return in_array($package->getType(), ['contao-module', 'contao-bundle', 'legacy-contao-module']);
                };
        }

        return $filters;
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
     * @param string    $keywords The search keywords.
     *
     * @param string    $type     The desired search type.
     *
     * @param Composer  $composer The composer instance.
     *
     * @return CompositeSearch
     */
    private function getRepositorySearch($keywords, $type, Composer $composer)
    {
        $repositoryManager = $composer->getRepositoryManager();
        $localRepository   = $repositoryManager->getLocalRepository();

        $repositories = new CompositeRepository(
            [
                new PlatformRepository(),
                $localRepository,
            ]
        );

        // If we do not search locally, add the other repositories now.
        if ('installed' !== $type) {
            $repositories->addRepository(new CompositeRepository($repositoryManager->getRepositories()));
        }

        $repositorySearch = new RepositorySearch($repositories);
        if (false !== strpos($keywords, '/')) {
            $repositorySearch->disableSearchType(RepositoryInterface::SEARCH_FULLTEXT);
        } else {
            $repositorySearch->disableSearchType(RepositoryInterface::SEARCH_NAME);
        }

        $searcher = new CompositeSearch(
            [
                $repositorySearch
            ]
        );

        return $searcher;
    }
}
