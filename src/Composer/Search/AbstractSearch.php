<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 15/05/15
 * Time: 12:52
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
     * @param int $numResults
     *
     * @return $this
     */
    public function satisfiedBy($numResults)
    {
        return $this->setSatisfactionThreshold($numResults);
    }
}