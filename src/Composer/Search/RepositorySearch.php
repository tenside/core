<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:03
 */

namespace Tenside\Composer\Search;

use Composer\IO\BufferIO;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Util\RemoteFilesystem;
use Tenside\Composer\Package\VersionedPackage;
use Tenside\Util\JsonArray;

/**
 * Class RepositorySearch
 *
 * @package Tenside\Composer
 */
class RepositorySearch extends AbstractSearch
{

    /**
     * @var array
     */
    protected $enabledSearchTypes = [
        RepositoryInterface::SEARCH_NAME,
        RepositoryInterface::SEARCH_FULLTEXT
    ];

    /**
     * @var CompositeRepository|null
     */
    protected $repositories;

    /**
     * @param CompositeRepository $repositories
     */
    function __construct(CompositeRepository $repositories = null)
    {
        $this->repositories = $repositories;
    }

    /**
     * {@inheritDoc}
     */
    public function searchFully($keywords)
    {
        $results = [];

        foreach ($this->enabledSearchTypes as $searchType) {
            $results = array_merge(
                $results,
                $this->repositories->search($keywords, $searchType)
            );
        }

        return $this->normalizeResultSet($results);
    }

    /**
     * {@inheritDoc}
     */
    public function search($keywords)
    {
        $results = [];

        foreach ($this->enabledSearchTypes as $searchType) {
            $results = array_merge(
                $results,
                $this->repositories->search($keywords, $searchType)
            );

            if (count($results) >= $this->getSatisfactionThreshold()) {
                $results = array_slice($results, 0, $this->getSatisfactionThreshold());
                break;
            }
        }

        return $this->normalizeResultSet($results);
    }

    /**
     * {@inheritDoc}
     */
    public function searchAndDecorate($keywords)
    {
        $results = $this->search($keywords);

        $decorated = [];

        foreach ($results as $packageName) {
            $decorated[] = $this->decorate($packageName);
        }

        return $decorated;
    }

    /**
     * @param string $packageName
     *
     * @return PackageInterface
     */
    protected function decorate($packageName)
    {

        $results = $this->repositories->findPackages($packageName);

        if (!count($results)) {
            throw new \InvalidArgumentException("Could not find package with specified name " . $packageName);
        }

        $latest   = array_slice($results, 0, 1)[0];
        $versions = array_slice($results, 1);
        $package  = new VersionedPackage($latest, $versions);

        return $this->decorateWithPackagistStats($package);

    }

    /**
     * @param VersionedPackage $package
     *
     * @return VersionedPackage
     */
    protected function decorateWithPackagistStats(VersionedPackage $package)
    {

        $rfs        = new RemoteFilesystem(new BufferIO());
        $requestUrl = sprintf('http://packagist.org/packages/%1$s.json', $package->getName());
        $jsonData   = $rfs->getContents($requestUrl, $requestUrl);
        $data       = new JsonArray($jsonData);

        $metaPaths = [
            'downloads' => 'package/downloads/total',
            'favers'    => 'favers'
        ];

        foreach ($metaPaths as $metaKey => $metaPath) {
            $package->addMetaData($metaKey, $data->get($metaPath));
        }

        return $package;
    }

    /**
     * @return CompositeRepository|null
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * @param $repositories
     *
     * @return $this
     */
    public function setRepositories(CompositeRepository $repositories)
    {
        $this->repositories = $repositories;

        return $this;
    }

    /**
     * @param int[] $searchTypes
     *
     * @return $this
     */
    public function enableSearchTypes(array $searchTypes)
    {
        foreach ($searchTypes as $searchType) {
            $this->enableSearchType($searchType);
        }

        return $this;
    }

    /**
     * @param int $searchType
     *
     * @return $this
     */
    public function enableSearchType($searchType)
    {
        $this->enabledSearchTypes[] = $searchType;
        return $this;
    }

    /**
     * @param int[] $searchTypes
     *
     * @return $this
     */
    public function disableSearchTypes(array $searchTypes)
    {
        foreach ($searchTypes as $searchType) {
            $this->disableSearchType($searchType);
        }

        return $this;
    }

    /**
     * @param int $searchType
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