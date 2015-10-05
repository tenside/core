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

namespace Tenside\Test\Task;

use Tenside\Task\RequirePackageTask;
use Tenside\Test\TestCase;
use Tenside\Util\JsonArray;

/**
 * This class tests the install task.
 */
class RequirePackageTaskTest extends TestCase
{
    /**
     * Test that the base functionality works.
     *
     * @return void
     */
    public function testAll()
    {
        // Redirect composer config and cache into the test temp dir.
        putenv('COMPOSER_HOME=' . $this->getTempDir() . DIRECTORY_SEPARATOR . '.composer');
        $this->createFixture(
            '.composer' . DIRECTORY_SEPARATOR . 'config.json',
            str_replace(
                '##URL##',
                $this->getTempDir() . DIRECTORY_SEPARATOR . 'test-repository' . DIRECTORY_SEPARATOR,
                $this->readFixture('composer' . DIRECTORY_SEPARATOR . 'config.json')
            )
        );

        $this->createFixture(
            'test-repository' . DIRECTORY_SEPARATOR. 'packages.json',
            str_replace(
                '##URL##',
                $this->getTempDir() . DIRECTORY_SEPARATOR . 'test-repository' . DIRECTORY_SEPARATOR,
                $this->readFixture('test-repository' . DIRECTORY_SEPARATOR . 'packages.json')
            )
        );

        $this->provideFixture($zip = 'test-repository' . DIRECTORY_SEPARATOR. 'vendor-dependency-name-1.0.0.zip');

        // First we need a empty installation.
        $this->createFixture('composer.json', json_encode(
            [
                'name'        => 'test/website',
                'description' => 'Some description',
                'license'     => 'MIT',
            ]
        ));

        $task = new RequirePackageTask(
            new JsonArray(
                [
                    RequirePackageTask::SETTING_TYPE    => 'require-package',
                    RequirePackageTask::SETTING_ID      => 'require-task-id',
                    RequirePackageTask::SETTING_PACKAGE => ['vendor/dependency-name', '1.0.0'],
                    RequirePackageTask::SETTING_HOME    => $this->getTempDir(),
                ]
            )
        );

        $task->perform($this->getTempFile('logs/require-task.log'));

        $this->assertEquals(RequirePackageTask::STATE_FINISHED, $task->getStatus());
        $this->assertContains('Installing vendor/dependency-name (1.0.0)', $task->getOutput());

        $this->assertZipHasBeenUnpackedTo(
            $this->getFixturesDirectory() . DIRECTORY_SEPARATOR . $zip,
            'vendor' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dependency-name'
        );
    }
}
