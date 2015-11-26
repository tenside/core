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

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Symfony\Component\Routing\Route;
use Tenside\CoreBundle\Annotation\ApiDescription;

/**
 * This class parses the API annotation.
 */
class TensideApiDocHandler implements HandlerInterface
{
    /**
     * The field names of parameters to convert by default.
     *
     * @var array
     */
    public static $convertFields = [
        'default',
        'description',
        'format',
        'actualType',
        'subType',
        'sinceVersion',
        'untilVersion',
        'requirement'
    ];

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        foreach ($annotations as $description) {
            if (!($description instanceof ApiDescription)) {
                continue;
            }

            $current = $annotation->toArray();

            $request = [];
            foreach ($description->getRequest() as $name => $field) {
                $request[$name] = $this->convertField($field);
            }
            $annotation->setParameters($request);

            if (!isset($current['response'])) {
                $response = [];
                foreach ($description->getResponse() as $name => $field) {
                    $response[$name] = $this->convertField($field);
                }

                $annotation->setResponse($response);
            }
        }
    }

    /**
     * Convert the annotation for a field.
     *
     * @param array $array The information for the field.
     *
     * @return array
     */
    private function convertField($array)
    {
        $result = [];

        // Copy over well known keys.
        foreach (static::$convertFields as $key) {
            if (isset($array[$key])) {
                $result[$key] = $array[$key];
            }
        }

        if (isset($array['dataType'])) {
            $result['dataType'] = $this->inferType($array['dataType']);
        } else {
            $result['dataType'] = isset($array['children']) ? 'object' : DataTypes::STRING;
        }

        $result['required'] = isset($array['children']) && (bool) $array['children'];
        $result['readonly'] = isset($array['readonly']) && (bool) $array['readonly'];

        if (isset($array['children'])) {
            foreach ($array['children'] as $key => $value) {
                $result['children'][$key] = $this->convertField($value);
            }
        }

        return $result;
    }

    /**
     * Convert the type.
     *
     * @param string $type The type name.
     *
     * @return string
     */
    public function inferType($type)
    {
        if (DataTypes::isPrimitive($type)) {
            return $type;
        } elseif (DataTypes::COLLECTION === strtolower($type)) {
            return $type;
        }
        return DataTypes::STRING;
    }
}
