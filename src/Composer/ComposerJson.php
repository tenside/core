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

namespace Tenside\Composer;

use Tenside\Util\JsonFile;

/**
 * This class abstracts the composer.json file manipulation.
 */
class ComposerJson extends JsonFile
{
    /**
     * Add a requirement to composer.json.
     *
     * @param string $name       The package name.
     *
     * @param string $constraint The version constraint.
     *
     * @return ComposerJson
     */
    public function requirePackage($name, $constraint)
    {
        return $this->setLink('require', $name, $constraint);
    }

    /**
     * Add a requirement to composer.json for dev.
     *
     * @param string $name       The package name.
     *
     * @param string $constraint The version constraint.
     *
     * @return ComposerJson
     */
    public function requirePackageDev($name, $constraint)
    {
        return $this->setLink('require-dev', $name, $constraint);
    }

    /**
     * Add a replacement to composer.json.
     *
     * @param string $name       The package name.
     *
     * @param string $constraint The version constraint.
     *
     * @return ComposerJson
     */
    public function replacePackage($name, $constraint)
    {
        return $this->setLink('replace', $name, $constraint);
    }

    /**
     * Add a replacement to composer.json.
     *
     * @param string $name       The package name.
     *
     * @param string $constraint The version constraint.
     *
     * @return ComposerJson
     */
    public function providePackage($name, $constraint)
    {
        return $this->setLink('provide', $name, $constraint);
    }

    /**
     * Get a requirement from composer.json.
     *
     * @param string $name The package name.
     *
     * @return string|null
     */
    public function getRequire($name)
    {
        return $this->getLink('require', $name);
    }

    /**
     * Get a requirement from composer.json for dev.
     *
     * @param string $name The package name.
     *
     * @return string|null
     */
    public function getRequireDev($name)
    {
        return $this->getLink('require-dev', $name);
    }

    /**
     * Get a requirement from composer.json.
     *
     * @param string $name The package name.
     *
     * @return string|null
     */
    public function getReplace($name)
    {
        return $this->getLink('replace', $name);
    }

    /**
     * Get a requirement from composer.json.
     *
     * @param string $name The package name.
     *
     * @return string|null
     */
    public function getProvide($name)
    {
        return $this->getLink('provide', $name);
    }

    /**
     * Check if a require entry has been defined.
     *
     * @param string $name The package name.
     *
     * @return bool
     */
    public function isRequiring($name)
    {
        return $this->hasLink('require', $name);
    }

    /**
     * Check if a require-dev entry has been defined.
     *
     * @param string $name The package name.
     *
     * @return bool
     */
    public function isRequiringDev($name)
    {
        return $this->hasLink('require-dev', $name);
    }

    /**
     * Check if a replacement entry has been defined.
     *
     * @param string $name The package name.
     *
     * @return bool
     */
    public function isReplacing($name)
    {
        return $this->hasLink('replace', $name);
    }

    /**
     * Check if a provide entry has been defined.
     *
     * @param string $name The package name.
     *
     * @return bool
     */
    public function isProviding($name)
    {
        return $this->hasLink('provide', $name);
    }

    /**
     * Set a link on a dependency to a constraint.
     *
     * @param string $type       The link type (require, require-dev, provide, replace).
     *
     * @param string $name       The package name.
     *
     * @param string $constraint The version constraint.
     *
     * @return ComposerJson
     */
    private function setLink($type, $name, $constraint)
    {
        $this->set($type . '/' . $this->escape($name), $constraint);

        return $this;
    }

    /**
     * Check if a link has been defined.
     *
     * @param string $type The link type (require, require-dev, provide, replace).
     *
     * @param string $name The package name.
     *
     * @return bool
     */
    private function hasLink($type, $name)
    {
        return $this->has($type . '/' . $this->escape($name));
    }

    /**
     * Check if a link has been defined.
     *
     * @param string $type The link type (require, require-dev, provide, replace).
     *
     * @param string $name The package name.
     *
     * @return string|null
     */
    private function getLink($type, $name)
    {
        return $this->get($type . '/' . $this->escape($name));
    }
}
