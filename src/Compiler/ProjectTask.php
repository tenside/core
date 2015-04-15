<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Compiler;

use Tenside\Compiler;

/**
 * The Compiler class compiles tenside into a phar.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ProjectTask extends AbstractTask
{
    /**
     * Run the compile task.
     *
     * @return void
     */
    public function compile()
    {
        // Add autoload information.
        // FIXME: optimize autoloader here.
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/autoload.php'));
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/autoload_namespaces.php'));
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/autoload_psr4.php'));
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/autoload_classmap.php'));
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/autoload_real.php'));
        if (file_exists($this->getVendorDir() . '/composer/include_paths.php')) {
            $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/include_paths.php'));
        }

        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/composer/ClassLoader.php'));
        $this->addFile(new \SplFileInfo($this->getVendorDir() . '/../LICENSE'), false);
    }
}
