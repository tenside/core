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

namespace Tenside\Test\Web\Controller;

use Symfony\Component\HttpFoundation\Response;
use Tenside\Web\Controller\AbstractRestrictedController;

/**
 * This class is used for the AbstractRestrictedControllerTest.
 */
class DummyRestrictedController extends AbstractRestrictedController
{
    /**
     * The needed level for the action.
     *
     * @var int
     */
    private $neededLevel;

    /**
     * Create a new instance.
     *
     * @param int $neededLevel The needed level for the test method.
     */
    public function __construct($neededLevel)
    {
        $this->neededLevel = $neededLevel;
    }

    /**
     * Dummy no op method..
     *
     * @return Response
     */
    public function getDummyAction()
    {
        $this->needAccessLevel($this->neededLevel);

        return new Response('dummy');
    }
}
