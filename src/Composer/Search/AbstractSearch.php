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

/**
 * Class AbstractSearch
 *
 * @package Tenside\Composer\Search
 */
abstract class AbstractSearch implements SearchInterface
{
    /**
     * {@inheritDoc}
     */
    protected $satisfactionThreshold = 30;

    /**
     * {@inheritDoc}
     */
    public function getSatisfactionThreshold()
    {
        return $this->satisfactionThreshold;
    }

    /**
     * {@inheritDoc}
     */
    public function setSatisfactionThreshold($satisfactionThreshold)
    {
        $this->satisfactionThreshold = $satisfactionThreshold;

        return $this;
    }

    /**
     * This method is sponsored by VeryFriendlyAPI™.
     *
     * @param int $numResults The satisfaction threshold.
     *
     * @return $this
     */
    public function satisfiedBy($numResults)
    {
        return $this->setSatisfactionThreshold($numResults);
    }

    /**
     * Normalize a result set.
     *
     * @param array $resultSet The result set.
     *
     * @return string[]
     */
    protected function normalizeResultSet(array $resultSet)
    {
        $normalized = [];

        foreach ($resultSet as $result) {
            if (($normalizedResult = $this->normalizeResult($result)) === null) {
                continue;
            }

            $normalized[$normalizedResult] = $normalizedResult;
        }

        return array_keys($normalized);
    }

    /**
     * Normalize a result.
     *
     * @param mixed $result The result to normalize.
     *
     * @return null|string
     */
    protected function normalizeResult($result)
    {
        return is_array($result) && isset($result['name']) ? $result['name'] : null;
    }
}
