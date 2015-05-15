<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:29
 */

namespace Tenside\Composer\Search;

use Composer\Package\PackageInterface;
use Tenside\Composer\Search\SearchInterface;

/**
 * Class CompositeSearch
 *
 * @package Tenside\Composer
 */
class CompositeSearch extends AbstractSearch
{

    /**
     * @var SearchInterface[]
     */
    protected $searchers;

    function __construct(array $searchers)
    {
        $this->addSearchers($searchers);
    }

    /**
     * {@inheritdoc}
     */
    public function search($keywords)
    {
        $results = [];

        foreach ($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->search($keywords)
            );

            if (count($results) >= $this->getSatisfactionThreshold()) {
                return array_slice($results, 0, $this->getSatisfactionThreshold());
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function searchAndDecorate($keywords)
    {
        $results = [];

        foreach($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->searchAndDecorate($keywords)
            );

            if (count($results) >= $this->getSatisfactionThreshold()) {
                return array_slice($results, 0, $this->getSatisfactionThreshold());
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function searchFully($keywords)
    {
        $results = [];

        foreach ($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->search($keywords)
            );
        }

        return $results;
    }


    public function getSearchers()
    {
        return $this->searchers;
    }

    /**
     * @param SearchInterface $searcher
     *
     * @return $this
     */
    public function addSearcher(SearchInterface $searcher)
    {
        if($searcher instanceof self && count($searcher->getSearchers())) {
            foreach($searcher->getSearchers() as $compositeSearcher) {
                $this->addSearcher($compositeSearcher);
            }

            return $this;
        }

        $this->searchers[] = $searcher;

        return $this;
    }

    /**
     * @param array $searchers
     *
     * @return $this
     */
    public function addSearchers(array $searchers)
    {
        foreach ($searchers as $searcher) {
            $this->addSearcher($searcher);
        }

        return $this;
    }

}