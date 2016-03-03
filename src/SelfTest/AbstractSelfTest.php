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
 * This class is the abstract base for performing checks that the current environment is suitable for running tenside.
 */
abstract class AbstractSelfTest
{
    /**
     * The detailed message of the test result.
     *
     * @var string
     */
    private $message;

    /**
     * The optional message that might shed some light on any problem that occurred.
     *
     * @var string
     */
    private $explain;

    /**
     * The test result state.
     *
     * @var string
     *
     * @see TestResult::STATE_SUCCESS, TestResult::STATE_FAIL, TestResult::STATE_SKIPPED.
     */
    private $state;

    /**
     * The auto config the test shall write to.
     *
     * @var AutoConfig
     */
    private $autoConfig;

    /**
     * Run the test and return the result.
     *
     * @param AutoConfig $autoConfig The auto config to write config values to.
     *
     * @return SelfTestResult
     *
     * @throws \RuntimeException When anything went wrong.
     */
    public function perform(AutoConfig $autoConfig)
    {
        $this->autoConfig = $autoConfig;

        $this->prepare();
        try {
            $this->doTest();
        } catch (\Exception $exception) {
            $this->finalize();

            throw new \RuntimeException('Failed to execute test.', 0, $exception);
        }

        $this->finalize();

        return new SelfTestResult($this->message, $this->state, get_class($this), $this->explain);
    }

    /**
     * Retrieve autoConfig
     *
     * @return AutoConfig
     */
    protected function getAutoConfig()
    {
        return $this->autoConfig;
    }

    /**
     * Any initialization code.
     *
     * @return void
     */
    protected function prepare()
    {
        // No-op in this class.
    }

    /**
     * Any initialization code.
     *
     * @return void
     */
    protected function finalize()
    {
        // No-op in this class.
    }

    /**
     * Run the test and return the result.
     *
     * @return void
     */
    abstract protected function doTest();

    /**
     * Mark this test as successful.
     *
     * @param string|null $explain An optional additional explanation.
     *
     * @return void
     */
    protected function markSuccess($explain = null)
    {
        $this->state = SelfTestResult::STATE_SUCCESS;
        if (null !== $explain) {
            $this->setExplain($explain);
        }
    }

    /**
     * Mark this test as failing.
     *
     * @param string|null $explain An optional additional explanation.
     *
     * @return void
     */
    protected function markFailed($explain = null)
    {
        $this->state = SelfTestResult::STATE_FAIL;
        if (null !== $explain) {
            $this->setExplain($explain);
        }
    }

    /**
     * Mark this test as skipped.
     *
     * @param string|null $explain An optional additional explanation.
     *
     * @return void
     */
    protected function markSkipped($explain = null)
    {
        $this->state = SelfTestResult::STATE_SKIPPED;
        if (null !== $explain) {
            $this->setExplain($explain);
        }
    }

    /**
     * Mark this test as warning.
     *
     * @param string|null $explain An optional additional explanation.
     *
     * @return void
     */
    protected function markWarning($explain = null)
    {
        $this->state = SelfTestResult::STATE_WARN;
        if (null !== $explain) {
            $this->setExplain($explain);
        }
    }

    /**
     * Set the optional explanation.
     *
     * @param string $explain An optional additional explanation.
     *
     * @return void
     */
    protected function setExplain($explain)
    {
        $this->explain = $explain;
    }

    /**
     * Set the description of the test.
     *
     * @param string $message The description message of this test.
     *
     * @return void
     */
    protected function setMessage($message)
    {
        $this->message = $message;
    }
}
