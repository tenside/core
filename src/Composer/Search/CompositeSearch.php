<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:29
 */

namespace Tenside\Composer\Search;

use Tenside\Composer\Search\SearchInterface;

/**
 * Class CompositeSearch
 *
 * @package Tenside\Composer
 */
class CompositeSearch implements SearchInterface
{

    /**
     * The satisfaction threshold indicates the
     * minimum number of search results needed
     * to bail from searching early.
     */
    const SATISFACTION_THRESHOLD = 10;

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

            if (count($results) >= self::SATISFACTION_THRESHOLD) {
                return array_slice($results, 0, self::SATISFACTION_THRESHOLD);
            }
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