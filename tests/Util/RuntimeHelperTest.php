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

namespace Tenside\Test\Util;

use Tenside\Test\TestCase;
use Tenside\Util\RuntimeHelper;

/**
 * Test the RuntimeHelper.
 */
class RuntimeHelperTest extends TestCase
{
    /**
     * Buffer the COMPOSER env variable.
     *
     * @var string
     */
    private $envComposer;

    /**
     * Buffer the HOME env variable.
     *
     * @var string
     */
    private $envHome;

    /**
     * Buffer the COMPOSER_HOME env variable.
     *
     * @var string
     */
    private $envComposerHome;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->envComposer     = getenv('COMPOSER');
        $this->envHome         = getenv('HOME');
        $this->envComposerHome = getenv('COMPOSER_HOME');
        putenv('COMPOSER');
        putenv('HOME');
        putenv('COMPOSER_HOME');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (false !== $this->envComposer) {
            putenv('COMPOSER=' . $this->envComposer);
        }
        if (false !== $this->envHome) {
            putenv('HOME=' . $this->envHome);
        }
        if (false !== $this->envComposerHome) {
            putenv('COMPOSER_HOME=' . $this->envComposerHome);
        }

        parent::tearDown();
    }

    /**
     * Test the setupHome method.
     *
     * @return void
     */
    public function testSetupHome()
    {
        putenv('HOME=' . $this->getTempDir());

        RuntimeHelper::setupHome($this->getTempDir());

        $this->assertEquals($this->getTempFile('composer.json'), getenv('COMPOSER'));
        $this->assertEquals($this->getTempDir(), getcwd());
    }

    /**
     * Test the setupHome method.
     *
     * @return void
     */
    public function testSetupHomeWithComposerEnv()
    {
        putenv('HOME=' . $this->getTempDir());
        $testDir      = $this->getTempDir() . DIRECTORY_SEPARATOR . 'subdir';
        $composerJson = $testDir . DIRECTORY_SEPARATOR . 'composer.json';

        mkdir($testDir);
        putenv('COMPOSER=' . $composerJson);

        RuntimeHelper::setupHome($testDir);

        $this->assertEquals($composerJson, getenv('COMPOSER'));
        $this->assertEquals($testDir, getcwd());
    }

    /**
     * Test the setupHome method.
     *
     * @return void
     */
    public function testSetupHomeSetsComposerHomeWhenNoHome()
    {
        $testDir      = $this->getTempDir() . DIRECTORY_SEPARATOR . 'subdir';
        $composerJson = $testDir . DIRECTORY_SEPARATOR . 'composer.json';

        mkdir($testDir);

        RuntimeHelper::setupHome($testDir);

        $this->assertEquals($composerJson, getenv('COMPOSER'));
        $this->assertEquals($testDir, getcwd());
        $this->assertEquals($testDir, getenv('COMPOSER_HOME'));
    }

    /**
     * Test the setupHome method.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSetupHomeBailsWithEmptyHome()
    {
        RuntimeHelper::setupHome('');
    }
}
