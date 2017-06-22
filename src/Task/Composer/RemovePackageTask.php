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

namespace Tenside\Core\Task\Composer;

use Symfony\Component\Console\Input\ArrayInput;
use Tenside\Core\Task\Composer\WrappedCommand\RemoveCommand;
use Tenside\Core\Util\RuntimeHelper;

/**
 * This class holds the information for an installation request of a package (composer remove).
 */
class RemovePackageTask extends AbstractPackageManipulatingTask
{
    /**
     * Returns 'upgrade'.
     *
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'remove-package';
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareCommand()
    {
        // Switch home first, this is needed as the command manipulates the RAW composer.json prior to creating the
        // composer instance.
        RuntimeHelper::setupHome($this->getHome());

        return $this->attachComposerFactory(new RemoveCommand());
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInput()
    {
        $arguments = [
            'packages' => $this->getPackage(),
            '--no-progress' => true,
            '--optimize-autoloader' => true,
        ];

        if ($this->isNoUpdate()) {
            $arguments['--no-update'] = true;
        } else {
            $arguments['--update-no-dev'] = true;
        }

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $input;
    }
}
