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

namespace Tenside\CoreBundle\Annotation;

use Symfony\Component\Routing\Route;

/**
 * Annotation for API results.
 *
 * @Annotation
 */
class ApiDescription
{
    /**
     * The request payload description.
     *
     * @var array
     */
    private $request = [];

    /**
     * The response description.
     *
     * @var array
     */
    private $response = [];

    /**
     * Link to another route and inherit the doc from it.
     *
     * @var string
     */
    private $link;

    /**
     * Create a new instance.
     *
     * @param array $options The values from the annotation.
     */
    public function __construct($options)
    {
        if (isset($options['request'])) {
            $this->request = (array) $options['request'];
            unset($options['request']);
        }

        if (isset($options['response'])) {
            $this->response = (array) $options['response'];
            unset($options['response']);
        }

        if (isset($options['link'])) {
            $this->link = $options['link'];
            unset($options['link']);
        }
    }

    /**
     * Retrieve the request.
     *
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Retrieve the response.
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retrieve the link.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }
}
