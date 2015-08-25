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

namespace Tenside\Task;

/**
 * This class holds the information for an upgrade of some or all packages.
 */
class UpgradeTask extends Task
{
    /**
     * Retrieve the names of the packages to upgrade or null if none.
     *
     * @return string[]|null
     */
    public function getPackages()
    {
        return $this->file->get('packages');
    }

    /**
     * Check if the upgrade is selective or for all packages.
     *
     * @return bool
     */
    public function isSelectiveUpgrade()
    {
        return (null !== $this->getPackages());
    }

    /**
     * Returns 'upgrade'.
     *
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'upgrade';
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function perform()
    {
        $this->setStatus($this::STATE_RUNNING);

        $inputOutput = $this->getIO();
        for ($i = 0; $i < 200; $i++) {
            $inputOutput->write('Hello ' . $i);

            sleep(5);
        }

        $this->setStatus($this::STATE_FINISHED);
    }
}
