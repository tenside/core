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

namespace Tenside\Test;

use Symfony\Component\Filesystem\Filesystem;

/**
 * This class tests the task list.
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Temporary working dir.
     *
     * @var string
     */
    protected $workDir;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (isset($this->workDir)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->workDir);
        }
    }

    /**
     * Retrieve the path to the fixtures directory.
     *
     * @return string
     */
    protected function getFixturesDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
    }

    /**
     * Create and return the path to a temp dir.
     *
     * @return string
     */
    protected function getTempDir()
    {
        if (!isset($this->workDir)) {
            $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('tenside-core-test');
            mkdir($temp, 0777, true);
            $this->workDir = $temp;
        }

        return $this->workDir;
    }

    /**
     * Retrieve the path of a temp file within the temp dir of the test.
     *
     * @param string $name Optional name of the file.
     *
     * @return string
     */
    public function getTempFile($name = '')
    {
        if ('' === $name) {
            $name = uniqid();
        }

        return $this->getTempDir() . DIRECTORY_SEPARATOR . $name;
    }
}
