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

namespace Tenside\Core\Test\Util;

use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\JsonFile;

/**
 * Test the JsonArray handler.
 */
class JsonFileTest extends TestCase
{
    /**
     * Test that the file can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $filename = $this->getTempFile('test.json');

        $json = new JsonFile($filename);

        $this->assertInstanceOf('Tenside\\Core\\Util\\JsonFile', $json);
        $this->assertEquals($filename, $json->getFilename());
        $this->assertFileNotExists($filename);
        $this->assertFileNotExists($filename . '~');
    }

    /**
     * Test that the file gets created when not existent.
     *
     * @return void
     */
    public function testCreatesFile()
    {
        $filename = $this->getTempFile('test.json');

        $json = new JsonFile($filename);

        $this->assertEquals($json, $json->set('foo', 'bar'));

        $this->assertFileExists($filename);
        $this->assertFileNotExists($filename . '~');
    }

    /**
     * Test that the backup file gets created.
     *
     * @return void
     */
    public function testCreatesBackupFile()
    {
        $filename = $this->createFixture('test.json', '{}');

        $json = new JsonFile($filename);

        $this->assertEquals($json, $json->set('foo', 'bar'));

        $this->assertFileExists($filename);
        $this->assertFileExists($filename . '~');
    }

    /**
     * Test that the backup filename can be overridden.
     *
     * @return void
     */
    public function testBackupFileNameOverride()
    {
        $filename = $this->createFixture('test.json', '{}');

        $json = new JsonFile($filename, $filename . '.backup');

        $this->assertEquals($json, $json->set('foo', 'bar'));

        $this->assertFileExists($filename);
        $this->assertFileExists($filename . '.backup');
    }

    /**
     * Test that the backup filename can be overridden to a non existent other directory.
     *
     * @return void
     */
    public function testBackupFileNameOverrideToNonExistentOtherDir()
    {
        $filename   = $this->createFixture('test.json', '{}');
        $backupFile = $this->getTempDir() . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'test.json';

        $json = new JsonFile($filename, $backupFile);

        $this->assertEquals($json, $json->set('foo', 'bar'));

        $this->assertFileExists($filename);
        $this->assertFileExists($backupFile);
    }

    /**
     * Test that the file gets created when not existent.
     *
     * @return void
     */
    public function testCreatesFileInNonExistentDirectoryWithBackupFileInDistinctOtherDir()
    {
        $filename = $this->getTempDir() . DIRECTORY_SEPARATOR . 'realfiles' . DIRECTORY_SEPARATOR . 'test.json';
        $backup   = $this->getTempDir() . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'test.json';

        $json = new JsonFile($filename, $backup);

        $this->assertEquals($json, $json->set('foo', 'bar'));
        $this->assertEquals($json, $json->set('foo', 'baz'));

        $this->assertFileExists($filename);
        $this->assertFileExists($backup);
    }

    /**
     * Test that an exception is thrown for invalid json data.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testBailsWithBrokenJson()
    {
        new JsonFile($this->createFixture('test.json', '{"foo": "bar",}'));
    }
}
