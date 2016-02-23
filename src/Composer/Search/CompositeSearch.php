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

/**
 * Class CompositeSearch
 *
 * @package Tenside\Composer
 */
class CompositeSearch extends AbstractSearch
{
    /**
     * The list of search providers.
     *
     * @var SearchInterface[]
     */
    protected $searchers;

    /**
     * Create a new instance.
     *
     * @param array $searchers The list of search providers.
     */
    public function __construct(array $searchers)
    {
        $this->addSearchers($searchers);
    }

    /**
     * {@inheritdoc}
     */
    public function search($keywords, $filters = [])
    {
        $results = [];

        foreach ($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->search($keywords, $filters)
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
    public function searchAndDecorate($keywords, $filters = [])
    {
        $results = [];

        foreach ($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->searchAndDecorate($keywords, $filters)
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
    public function searchFully($keywords, $filters = [])
    {
        $results = [];

        foreach ($this->getSearchers() as $searcher) {
            $results = array_merge(
                $results,
                $searcher->search($keywords, $filters)
            );
        }

        return $results;
    }

    /**
     * Retrieve the list of search providers.
     *
     * @return SearchInterface[]
     */
    public function getSearchers()
    {
        return $this->searchers;
    }

    /**
     * Add a search providers,
     *
     * @param SearchInterface $searcher The provider to add.
     *
     * @return $this
     */
    public function addSearcher(SearchInterface $searcher)
    {
        if ($searcher instanceof self && count($searcher->getSearchers())) {
            foreach ($searcher->getSearchers() as $compositeSearcher) {
                $this->addSearcher($compositeSearcher);
            }

            return $this;
        }

        $this->searchers[] = $searcher;

        return $this;
    }

    /**
     * Add the passed search providers to the own list.
     *
     * @param array $searchers The providers to add.
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
