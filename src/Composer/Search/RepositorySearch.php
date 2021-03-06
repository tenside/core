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

namespace Tenside\Core\Composer\Search;

use Composer\IO\BufferIO;
use Composer\Package\PackageInterface;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Util\RemoteFilesystem;
use Tenside\Core\Composer\Package\VersionedPackage;
use Tenside\Core\Util\JsonArray;

/**
 * Class RepositorySearch
 *
 * @package Tenside\Composer
 */
class RepositorySearch extends AbstractSearch
{
    /**
     * The list of enabled search types.
     *
     * @var array
     */
    protected $enabledSearchTypes = [
        RepositoryInterface::SEARCH_NAME,
        RepositoryInterface::SEARCH_FULLTEXT
    ];

    /**
     * The repository to search on.
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * Base url for obtaining meta data (i.e. "https://packagist.org/packages/").
     *
     * @var string|null
     */
    private $decorateBaseUrl;

    /**
     * Create a new instance.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository      = $repository;
        $this->decorateBaseUrl = null;
        if ($this->repository instanceof ComposerRepository) {
            $repoConfig = $this->repository->getRepoConfig();
            if (!preg_match('{^[\w.]+\??://}', $repoConfig['url'])) {
                // assume https as the default protocol
                $repoConfig['url'] = 'https://' . $repoConfig['url'];
            }
            $this->decorateBaseUrl = rtrim($repoConfig['url'], '/') . '/packages/%1$s.json';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function searchFully($keywords, $filters = [])
    {
        $results = [];

        foreach ($this->enabledSearchTypes as $searchType) {
            $results = array_merge(
                $results,
                $this->repository->search($keywords, $searchType)
            );
        }

        return $this->filter($this->normalizeResultSet($results), $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function search($keywords, $filters = [])
    {
        $results = [];

        foreach ($this->enabledSearchTypes as $searchType) {
            $results = array_merge(
                $results,
                $this->filter($this->normalizeResultSet($this->repository->search($keywords, $searchType)), $filters)
            );

            if (count($results) >= $this->getSatisfactionThreshold()) {
                $results = array_slice($results, 0, $this->getSatisfactionThreshold());
                break;
            }
        }

        return array_values($results);
    }

    /**
     * {@inheritDoc}
     */
    public function searchAndDecorate($keywords, $filters = [])
    {
        $results = $this->search($keywords, $filters);

        $decorated = [];

        foreach ($results as $packageName) {
            try {
                $decorated[] = $this->decorate($packageName);
            } catch (\InvalidArgumentException $exception) {
                // Ignore the exception as some repositories return names they do not contain (i.e. replaced packages).
            }
        }

        return $decorated;
    }

    /**
     * Filter the passed list of package names.
     *
     * @param string[]   $packageNames The package names.
     *
     * @param \Closure[] $filters      The filters to apply.
     *
     * @return string[]
     */
    protected function filter($packageNames, $filters)
    {
        if (empty($filters)) {
            return $packageNames;
        }

        $packages = [];
        foreach ($packageNames as $packageName) {
            if (count($package = $this->repository->findPackages($packageName)) > 0) {
                foreach ($filters as $filter) {
                    $package = array_filter($package, $filter);
                }
                if ($package = current($package)) {
                    $packages[$packageName] = $package;
                }
            }
        }

        return array_map(
            function ($package) {
                /** @var PackageInterface $package */
                return $package->getName();
            },
            $packages
        );
    }

    /**
     * Decorate a package.
     *
     * @param string $packageName The name of the package to decorate.
     *
     * @return VersionedPackage
     *
     * @throws \InvalidArgumentException When the package could not be found.
     */
    protected function decorate($packageName)
    {
        $results = $this->repository->findPackages($packageName);

        if (!count($results)) {
            throw new \InvalidArgumentException('Could not find package with specified name ' . $packageName);
        }

        $latest   = array_slice($results, 0, 1)[0];
        $versions = array_slice($results, 1);
        $package  = new VersionedPackage($latest, $versions);

        return $this->decorateWithPackagistStats($package);
    }

    /**
     * Decorate the package with stats from packagist.
     *
     * @param VersionedPackage $package The package version.
     *
     * @return VersionedPackage
     */
    protected function decorateWithPackagistStats(VersionedPackage $package)
    {
        if (null === $this->decorateBaseUrl) {
            return $package;
        }

        $rfs        = new RemoteFilesystem(new BufferIO());
        $requestUrl = sprintf($this->decorateBaseUrl, $package->getName());
        if (!($jsonData = $rfs->getContents($requestUrl, $requestUrl))) {
            $this->decorateBaseUrl = null;
            return $package;
        }
        try {
            $data = new JsonArray($jsonData);
        } catch (\RuntimeException $exception) {
            $this->decorateBaseUrl = null;
            return  $package;
        }

        $metaPaths = [
            'downloads' => 'package/downloads/total',
            'favers'    => 'package/favers'
        ];

        foreach ($metaPaths as $metaKey => $metaPath) {
            $package->addMetaData($metaKey, $data->get($metaPath));
        }

        return $package;
    }

    /**
     * Retrieve the composite repository.
     *
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set the enabled search types.
     *
     * @param int[] $searchTypes The list of search types to enable.
     *
     * @return $this
     */
    public function enableSearchTypes($searchTypes)
    {
        foreach ((array) $searchTypes as $searchType) {
            $this->enableSearchType($searchType);
        }

        return $this;
    }

    /**
     * Enable a search type.
     *
     * @param int $searchType The search type to enable.
     *
     * @return $this
     */
    public function enableSearchType($searchType)
    {
        $this->enabledSearchTypes[] = $searchType;
        return $this;
    }

    /**
     * Disable the passed search types.
     *
     * @param int[] $searchTypes The search types to disable.
     *
     * @return $this
     */
    public function disableSearchTypes($searchTypes)
    {
        foreach ((array) $searchTypes as $searchType) {
            $this->disableSearchType($searchType);
        }

        return $this;
    }

    /**
     * Disable a search type.
     *
     * @param int $searchType The search type to disable.
     *
     * @return $this
     */
    public function disableSearchType($searchType)
    {
        if (($key = array_search($searchType, $this->enabledSearchTypes)) !== false) {
            unset($this->enabledSearchTypes[$key]);
        }

        return $this;
    }
}
