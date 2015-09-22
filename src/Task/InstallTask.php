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
     */
    public function perform()
    {
        $this->setStatus(self::STATE_RUNNING);

        if (!$this->mayInstall()) {
            $this->addOutput('Error: project directory not empty.');
            $this->setStatus(self::STATE_ERROR);

            return;
        }

        try {
            $this->prepareTmpDir();
        } catch (\RuntimeException $exception) {
            $this->addOutput('Error: ' . $exception->getMessage());
            $this->setStatus(self::STATE_ERROR);

            return;
        }
        $this->preserveEnvironment();

        try {
            $this->fetchProject();
            $this->moveFiles();
        } catch (\Exception $exception) {
            $this->addOutput('Error: ' . $exception->getMessage());
            $this->setStatus(self::STATE_ERROR);
            $this->restoreEnvironment();

            return;
        }

        $this->restoreEnvironment();

        $this->setStatus(self::STATE_FINISHED);
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
            $destinationFile = str_replace($this->tempDir, $destinationDir, $file->getPathName());
            $permissions     = substr(decoct(fileperms($file->getPathName())), 1);

            if ($file->isDir()) {
                $folders[] = $file->getPathname();
                if (!is_dir($destinationFile)) {
                    $ioHandler->write(sprintf(
                        'mkdir %s %s',
                        $file->getPathname(),
                        octdec($permissions)
                    ));
                    mkdir($destinationFile, octdec($permissions), true);
                }
            } else {
                $ioHandler->write(sprintf(
                    'move %s to %s',
                    $file->getPathname(),
                    $destinationFile
                ));
                copy($file->getPathname(), $destinationFile);
                chmod($destinationFile, octdec($permissions));
                unlink($file->getPathname());
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
