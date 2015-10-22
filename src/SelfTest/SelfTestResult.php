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

namespace Tenside\SelfTest;

/**
 * This class holds the results of an test.
 */
class SelfTestResult
{
    /**
     * Marks error state - dark red.
     */
    const STATE_FAIL = 'FAIL';

    /**
     * Marks that the test has been skipped/is not applicable in the current environment - lights off.
     */
    const STATE_SKIPPED = 'SKIPPED';

    /**
     * Marks success state - all lights are green.
     */
    const STATE_SUCCESS = 'SUCCESS';

    /**
     * Marks a warning state - bright shade of yellow or maybe orange.
     */
    const STATE_WARN = 'WARNING';

    /**
     * Optional description that could hint any problems and/or explain the error further.
     *
     * @var string
     */
    private $explain;

    /**
     * The detailed message of the test result.
     *
     * @var string
     */
    private $message;

    /**
     * The test result state.
     *
     * @var string
     *
     * @see SelfTestResult::STATE_FAIL
     * @see SelfTestResult::STATE_SKIPPED
     * @see SelfTestResult::STATE_SUCCESS
     * @see SelfTestResult::STATE_WARN
     */
    private $state;

    /**
     * The implementing class of the test.
     *
     * @var string
     */
    private $testClass;

    /**
     * Create a new instance.
     *
     * @param string $message   The detailed message of the test result.
     *
     * @param string $state     The test result state.
     *
     * @param string $testClass The implementing class of the test.
     *
     * @param string $explain   An optional description that could hint any problems.
     */
    public function __construct($message, $state, $testClass, $explain = '')
    {
        $this->message   = $message;
        $this->state     = $state;
        $this->testClass = $testClass;
        $this->explain   = $explain;
    }

    /**
     * Retrieve the message describing the test.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Retrieve the result state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Retrieve the class name that has created this test result.
     *
     * @return string
     */
    public function getTestClass()
    {
        return $this->testClass;
    }

    /**
     * Retrieve the optional explanation.
     *
     * @return string
     */
    public function getExplain()
    {
        return $this->explain;
    }
}
