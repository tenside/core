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
     * @param int $satisfactionThreshold The satisfaction threshold.
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
     * @param string     $keywords The search keywords.
     *
     * @param \Closure[] $filters  Optional filters for filtering the package instances.
     *
     * @return \string[]
     */
    public function search($keywords, $filters = []);

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of PackageInterface objects.
     *
     * This method MUST respect the satisfaction threshold,
     * and return a maximum number of results as returned
     * by the getSatisfactionThreshold() method.
     *
     * @param string     $keywords The search keywords.
     *
     * @param \Closure[] $filters  Optional filters for filtering the package instances.
     *
     * @return PackageInterface[]
     */
    public function searchAndDecorate($keywords, $filters = []);

    /**
     * The concrete searcher's search method takes a string
     * argument containing the search keywords, and returns
     * an array of fully-qualified package names.
     *
     * This method MUST NOT respect the satisfaction threshold,
     * and return all search results regardless of the
     * satisfacetion threshold.
     *
     * @param string     $keywords The search keywords.
     *
     * @param \Closure[] $filters  Optional filters for filtering the package instances.
     *
     * @return \string[]
     */
    public function searchFully($keywords, $filters = []);
}
