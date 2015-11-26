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

namespace Tenside\CoreBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tenside\CoreBundle\DependencyInjection\TensideCoreExtension;

/**
 * This class is the tenside core bundle.
 */
class TensideCoreBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new TensideCoreExtension();
    }

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        parent::boot();

        // Load our annotation if it get's mentioned, Doctrine does not try to autoload it via plain PHP.
        AnnotationRegistry::registerLoader(
            function ($class) {
                if (0 === strcmp('Tenside\CoreBundle\Annotation\ApiDescription', $class)) {
                    class_exists('Tenside\CoreBundle\Annotation\ApiDescription');
                    return true;
                }

                return false;
            }
        );
    }
}
