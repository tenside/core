<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:10
 */

namespace Tenside\Composer\Search;

use Composer\Package\PackageInterface;

/**
 * Interface SearchInterface
 *
 * @package Tenside\Composer
 */
interface SearchInterface
{

    /**
     * The satisfaction threshold indicates the minimum number
     * of search results needed to bail from searching through
     * repositories early. This threshold does NOT apply to
     * the searchFully() method.
     *
     * @return int
     */
    public function getSatisfactionThreshold();

    /**
     * Method to set a new satisfaction threshold
     * on the searcher.
     *
     * @param int $satisfactionThreshold
     *
     * @return $this
     */
    public function setSatisfactionThreshold($satisfactionThreshold);

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of fully-qualified package names.
     *
     * This method MUST respect the satisfaction threshold,
     * and return a maximum number of results as returned
     * by the getSatisfactionThreshold() method.
     *
     * @param string $keywords
     *
     * @return \string[]
     */
    public function search($keywords);

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of PackageInterface objects.
     *
     * This method MUST respect the satisfaction threshold,
     * and return a maximum number of results as returned
     * by the getSatisfactionThreshold() method.
     *
     * @param string $keywords
     *
     * @return PackageInterface[]
     */
    public function searchAndDecorate($keywords);

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of fully-qualified package names.
     *
     * This method MUST NOT respect the satisfaction threshold,
     * and return all search results regardless of the
     * satisfacetion threshold.
     *
     * @param string $keywords
     *
     * @return \string[]
     */
    public function searchFully($keywords);

}