<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:03
 */

namespace Tenside\Composer\Search;

use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryInterface;

/**
 * Class RepositorySearch
 *
 * @package Tenside\Composer
 */
class RepositorySearch implements SearchInterface
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
     * @param $keywords string
     */
    public function search($keywords) {

        $results = [];

        foreach($this->enabledSearchTypes as $searchType) {

            $results = array_merge(
                $results,
                $this->repositories->search($keywords, $searchType)
            );

        }

        return $results;

    }

    /**
     * @param int[] $searchTypes
     *
     * @return $this
     */
    public function enableSearchTypes(array $searchTypes) {
        foreach($searchTypes as $searchType) {
            $this->enableSearchType($searchType);
        }

        return $this;
    }

    /**
     * @param int $searchType
     *
     * @return $this
     */
    public function enableSearchType($searchType) {
        $this->enabledSearchTypes[] = $searchType;
        return $this;
    }

    /**
     * @param int[] $searchTypes
     *
     * @return $this
     */
    public function disableSearchTypes(array $searchTypes) {
        foreach($searchTypes as $searchType) {
            $this->disableSearchType($searchType);
        }

        return $this;
    }

    /**
     * @param int $searchType
     *
     * @return $this
     */
    public function disableSearchType($searchType) {
        if(($key = array_search($searchType, $this->enabledSearchTypes)) !== false) {
            unset($this->enabledSearchTypes[$key]);
        }

        return $this;
    }

}