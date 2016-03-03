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
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\SelfTest;

/**
 * This class checks that the current environment is suitable for running tenside.
 */
class SelfTest
{
    /**
     * The registered self tests.
     *
     * @var AbstractSelfTest[]
     */
    private $tests = [];

    /**
     * The auto configuration.
     *
     * @var AutoConfig
     */
    private $config;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->config = new AutoConfig();
    }

    /**
     * Run all tests and return the results as an error array.
     *
     * @return SelfTestResult[]
     */
    public function perform()
    {
        $results = [];

        foreach ($this->tests as $testInstance) {
            $results[] = $testInstance->perform($this->config);
        }

        return $results;
    }

    /**
     * Add a test instance to the list of tests to perform.
     *
     * @param AbstractSelfTest $test The test to add.
     *
     * @return void
     */
    public function addTest(AbstractSelfTest $test)
    {
        $this->tests[] = $test;
    }

    /**
     * Retrieve the auto config.
     *
     * @return AutoConfig
     */
    public function getAutoConfig()
    {
        return $this->config;
    }
}
