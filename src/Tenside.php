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

namespace Tenside;

use Composer\Composer;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use Tenside\Composer\ComposerJson;
use Tenside\Config\SourceInterface;
use Tenside\Task\TaskList;

/**
 * The main tenside instance.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class Tenside
{
    /**
     * The version.
     */
    const VERSION = '@package_version@';

    /**
     * The branch alias from the composer.json.
     */
    const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    /**
     * The release date.
     */
    const RELEASE_DATE = '@release_date@';

    /**
     * The config source to use.
     *
     * @var SourceInterface
     */
    private $configSource;

    /**
     * The home directory.
     *
     * @var string
     */
    private $home;

    /**
     * The input output buffer.
     *
     * @var IOInterface
     */
    private $inputOutput;

    /**
     * The composer instance.
     *
     * @var Composer
     */
    private $composer;

    /**
     * The command to call in the cli.
     *
     * @var string
     */
    private $cliExecutable;

    /**
     * The task list.
     *
     * @var TaskList
     */
    private $taskList;

    /**
     * Set the configuration source.
     *
     * @param SourceInterface $source The configuration source.
     *
     * @return Tenside
     */
    public function setConfigSource(SourceInterface $source)
    {
        $this->configSource = $source;

        return $this;
    }

    /**
     * Retrieve the configuration source.
     *
     * @return SourceInterface
     */
    public function getConfigSource()
    {
        return $this->configSource;
    }

    /**
     * Retrieve the configuration source.
     *
     * @return ComposerJson
     */
    public function getComposerJson()
    {
        return new ComposerJson($this->getHomeDir() . DIRECTORY_SEPARATOR . 'composer.json');
    }

    /**
     * Retrieve the temp directory.
     *
     * @return string
     */
    public function getTempDir()
    {
        // FIXME: make this configurable.
        return sys_get_temp_dir();
    }

    /**
     * Retrieve the tenside home dir containing the composer.json and vendor dir.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return $this->home;
    }

    /**
     * Set the home directory.
     *
     * @param string $home The new home directory.
     *
     * @return Tenside
     */
    public function setHome($home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Set the full path to the cli executable.
     *
     * @param string $cliExecutable The command to execute.
     *
     * @return Tenside
     */
    public function setCliExecutable($cliExecutable)
    {
        $this->cliExecutable = $cliExecutable;

        return $this;
    }

    /**
     * Retrieve the full path to the cli executable.
     *
     * @return string.
     */
    public function getCliExecutable()
    {
        return $this->cliExecutable;
    }

    /**
     * Set the input/output handler.
     *
     * @param IOInterface $inputOutput The instance to use.
     *
     * @return Tenside
     */
    public function setInputOutputHandler($inputOutput)
    {
        $this->inputOutput = $inputOutput;

        return $this;
    }

    /**
     * Retrieve the io instance.
     *
     * @return IOInterface
     */
    public function getInputOutputHandler()
    {
        return $this->inputOutput;
    }

    /**
     * Load composer.
     *
     * @return Composer
     */
    public function getComposer()
    {
        if (!isset($this->composer)) {
            $factory        = new ComposerFactory();
            $this->composer = $factory->createComposer(
                $this->getInputOutputHandler(),
                null,
                false,
                $this->getHomeDir()
            );
        }

        return $this->composer;
    }

    /**
     * Retrieve the task list.
     *
     * @return TaskList
     */
    public function getTaskList()
    {
        if (!isset($this->taskList)) {
            $this->taskList = new TaskList($this->getHomeDir());
        }

        return $this->taskList;
    }

    /**
     * Download an URL and return the content.
     *
     * @param string $url The url to retrieve.
     *
     * @return bool|string
     */
    public function download($url)
    {
        $rfs = new RemoteFilesystem($this->getInputOutputHandler());

        return $rfs->getContents($url, $url);
    }

    /**
     * Check if the installation is already done.
     *
     * @return bool
     */
    public function isInstalled()
    {
        return (file_exists($this->getHomeDir() . DIRECTORY_SEPARATOR . 'composer.json')
            && file_exists($this->getHomeDir() . DIRECTORY_SEPARATOR . 'tenside.json'));
    }
}
