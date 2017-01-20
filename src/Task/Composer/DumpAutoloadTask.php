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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\Task\Composer;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Tenside\Core\Task\Composer\WrappedCommand\DumpAutoloadCommand;
use Tenside\Core\Util\RuntimeHelper;

/**
 * This class holds the information for a dumpautoload task (composer dumpautoload).
 */
class DumpAutoloadTask extends AbstractComposerCommandTask
{
    /**
     * The home path of tenside.
     */
    const SETTING_HOME = 'home';

    /**
     * Constant for the optimize option.
     */
    const SETTING_OPTIMIZE = 'optimize';

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'dumpautoload';
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareCommand()
    {
        RuntimeHelper::setupHome((string) $this->file->get(self::SETTING_HOME));

        return $this->attachComposerFactory(new DumpAutoloadCommand());
    }

    /**
     * Prepare the input interface for the command.
     *
     * @return InputInterface
     */
    protected function prepareInput()
    {
        $arguments = [
            '--optimize' => (bool) $this->file->get(self::SETTING_OPTIMIZE),
            '--no-dev'   => true,
        ];

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $input;
    }
}
