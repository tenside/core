<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:10
 */

namespace Tenside\Composer\Search;

/**
 * Interface SearchInterface
 *
 * @package Tenside\Composer
 */
interface SearchInterface {

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of results.
     *
     * @param $keywords
     *
     * @return array
     */
    public function search($keywords);

}