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

namespace Tenside\Task;

use Composer\Command\CreateProjectCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * This class holds the information for an upgrade of some or all packages.
 */
class InstallTask extends Task
{
    /**
     * Constant for the package name key.
     */
    const SETTING_PACKAGE = 'package';

    /**
     * Constant for the package version key.
     */
    const SETTING_VERSION = 'version';

    /**
     * Constant for the destination dir key.
     */
    const SETTING_DESTINATION_DIR = 'dest-dir';

    /**
     * Constant for the repository url.
     */
    const SETTING_REPOSITORY_URL = 'repository-url';

    /**
     * Constant for the new user name.
     */
    const SETTING_USER = 'username';

    /**
     * Constant for the password of the new user.
     */
    const SETTING_PASSWORD = 'password';

    /**
     * The temporary directory to use.
     *
     * @var string
     */
    private $tempDir;

    /**
     * The environment variable storage.
     *
     * @var string|null
     */
    private $previousEnvVariable;

    /**
     * The previous working directory.
     *
     * @var string
     */
    private $previousWorkingDir;

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'install';
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException When the project directory is not empty or when the installation was not successful.
     */
    public function doPerform()
    {
        $this->setStatus(self::STATE_RUNNING);

        if (!$this->mayInstall()) {
            throw new \RuntimeException('Error: project directory not empty.');
        }

        // Will throw exception upon error.
        $this->prepareTmpDir();

        $this->preserveEnvironment();

        try {
            $this->fetchProject();
            $this->moveFiles();
        } catch (\Exception $exception) {
            $this->restoreEnvironment();
            throw new \RuntimeException('Error: ' . $exception->getMessage(), 1, $exception);
        }

        $this->restoreEnvironment();
    }


    /**
     * Prepare a temporary directory.
     *
     * @return void
     *
     * @throws \RuntimeException When an error occurred.
     */
    private function prepareTmpDir()
    {
        $tempDir = $this->file->get(self::SETTING_DESTINATION_DIR) . DIRECTORY_SEPARATOR . uniqid('install-');

        // If the temporary folder could not be created, error out.
        if (!mkdir($tempDir, 0700)) {
            throw new \RuntimeException('Error: Could not create the temporary directory');
        }

        $this->tempDir = $tempDir;
    }

    /**
     * Fetch the project into the given directory.
     *
     * @return void
     *
     * @throws \RuntimeException When an error occurred.
     */
    private function fetchProject()
    {
        $arguments = [
            'package'      => $this->file->get(self::SETTING_PACKAGE),
            'directory'    => $this->tempDir,
        ];

        if ($version = $this->file->get(self::SETTING_VERSION)) {
            $arguments['version'] = $version;
        }

        if ($repository = $this->file->get(self::SETTING_REPOSITORY_URL)) {
            $arguments['--repository-url'] = $repository;
        }

        $command = new CreateProjectCommand();
        $input   = new ArrayInput($arguments);
        $input->setInteractive(false);
        $command->setIO($this->getIO());

        try {
            if (0 !== ($statusCode = $command->run($input, new TaskOutput($this)))) {
                throw new \RuntimeException('Error: command exit code was ' . $statusCode);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Move the installed files to their intended destination.
     *
     * @return void
     */
    private function moveFiles()
    {
        // Ensure we have the file permissions not in cache as new files were installed.
        clearstatcache();
        // Now move all the files over.
        $destinationDir = $this->file->get(self::SETTING_DESTINATION_DIR);
        $folders        = [$this->tempDir];
        $ioHandler      = $this->getIO();
        foreach (Finder::create()->in($this->tempDir)->ignoreDotFiles(false) as $file) {
            /** @var SplFileInfo $file */
            $pathName        = $file->getPathname();
            $destinationFile = str_replace($this->tempDir, $destinationDir, $pathName);

            switch (true) {
                case $file->isDir():
                    $permissions = substr(decoct(fileperms($pathName)), 1);
                    $folders[]   = $pathName;
                    if (!is_dir($destinationFile)) {
                        $ioHandler->write(sprintf('mkdir %s (permissions: %s)', $pathName, $permissions));
                        mkdir($destinationFile, octdec($permissions), true);
                    }
                    break;

                case $file->isLink():
                    $target = readlink($pathName);
                    $ioHandler->write(sprintf('link %s to %s', $target, $destinationFile));
                    symlink($target, $destinationFile);
                    unlink($file->getPathname());

                    break;

                case $file->isFile():
                    $permissions = substr(decoct(fileperms($pathName)), 1);
                    $ioHandler->write(
                        sprintf('move %s to %s (permissions: %s)', $pathName, $destinationFile, $permissions)
                    );
                    copy($pathName, $destinationFile);
                    chmod($destinationFile, octdec($permissions));
                    unlink($file->getPathname());

                    break;

                default:
                    throw new \RuntimeException(
                        sprintf(
                            'Unknown file of type %s encountered for %s',
                            filetype($pathName),
                            $pathName
                        )
                    );
            }
        }

        foreach (array_reverse($folders) as $folder) {
            $ioHandler->write(sprintf('remove directory %s', $folder));
            rmdir($folder);
        }
    }

    /**
     * Check if we may install into the destination directory.
     *
     * @return bool
     */
    private function mayInstall()
    {
        $destinationDir = $this->file->get(self::SETTING_DESTINATION_DIR) . DIRECTORY_SEPARATOR;
        return !(file_exists($destinationDir . 'composer.json')
            /*&& file_exists($destinationDir . 'tenside.json')*/);
        // FIXME: need to determine this somehow better. Can not check for tenside also as we need the secret and user.
    }

    /**
     * Save the current environment variable and working directory.
     *
     * @return void
     */
    private function preserveEnvironment()
    {
        $this->previousEnvVariable = getenv('COMPOSER');
        $this->previousWorkingDir  = getcwd();
        // Clear any potential overriding env variable.
        putenv('COMPOSER=');
        chdir($this->tempDir);
    }

    /**
     * Restore the current environment variable and working directory.
     *
     * @return void
     */
    private function restoreEnvironment()
    {
        putenv('COMPOSER=' . $this->previousEnvVariable);
        chdir($this->previousWorkingDir);
    }
}
