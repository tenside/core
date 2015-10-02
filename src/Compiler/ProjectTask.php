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

namespace Tenside\Compiler;

use Tenside\Compiler;

/**
 * The Compiler class compiles tenside into a phar.
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
        $vendor = $this->getVendorDir();
        foreach ([
            '/autoload.php',
            '/composer/autoload_classmap.php',
            '/composer/autoload_files.php',
            '/composer/autoload_namespaces.php',
            '/composer/autoload_psr4.php',
            '/composer/autoload_real.php',
            '/composer/include_paths.php',
            '/composer/ClassLoader.php',
            '/../LICENSE',
        ] as $file) {
            if (file_exists($vendor . $file)) {
                $this->addFile(new \SplFileInfo($vendor . $file));
            }
        }
    }
}
