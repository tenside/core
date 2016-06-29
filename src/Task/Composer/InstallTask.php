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
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\Task\Composer;

use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tenside\Core\Task\Composer\WrappedCommand\CreateProjectCommand;

/**
 * This class holds the information for a fresh installation of a project (composer create-project).
 */
class InstallTask extends AbstractComposerCommandTask
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
     * The list of folders to remove after the installation was complete.
     *
     * @var string[]
     */
    private $folders;

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
        if (!$this->mayInstall()) {
            throw new \RuntimeException('Project directory not empty.');
        }

        // Will throw exception upon error.
        $this->prepareTmpDir();

        $this->preserveEnvironment();

        try {
            parent::doPerform();
            $this->moveFiles();
        } catch (\Exception $exception) {
            $this->restoreEnvironment();
            throw new \RuntimeException('Project could not be created.', 1, $exception);
        } finally {
            $this->restoreEnvironment();
        }

        rmdir($this->tempDir);
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
        // @codingStandardsIgnoreStart - we silence on purpose here as we create an exception on error.
        if (!@mkdir($tempDir, 0700)) {
        // @codingStandardsIgnoreEnd
            throw new \RuntimeException('Could not create the temporary directory');
        }

        $this->tempDir = $tempDir;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareCommand()
    {
        return $this->attachComposerFactory(new CreateProjectCommand());
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInput()
    {
        $arguments = [
            'package'   => $this->file->get(self::SETTING_PACKAGE),
            'directory' => $this->tempDir,
            '--prefer-dist',
            '--no-dev',
            '--no-interaction'
        ];

        if ($version = $this->file->get(self::SETTING_VERSION)) {
            $arguments['version'] = $version;
        }

        if ($repository = $this->file->get(self::SETTING_REPOSITORY_URL)) {
            $arguments['--repository-url'] = $repository;
        }

        $input = new ArrayInput($arguments);

        $input->setInteractive(false);

        return $input;
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
        $ioHandler      = $this->getIO();
        $logging        = $ioHandler->isVeryVerbose();
        $this->folders  = [];
        foreach (Finder::create()->in($this->tempDir)->ignoreDotFiles(false)->ignoreVCS(false) as $file) {
            $this->moveFile($file, $destinationDir, $logging, $ioHandler);
        }

        foreach (array_reverse($this->folders) as $folder) {
            if ($logging) {
                $ioHandler->write(sprintf('remove directory %s', $folder));
            }
            rmdir($folder);
        }
    }

    /**
     * Move a single file or folder.
     *
     * @param SplFileInfo $file      The file to move.
     *
     * @param string      $targetDir The destination directory.
     *
     * @param bool        $logging   Flag determining if actions shall get logged.
     *
     * @param IOInterface $ioHandler The io handler to log to.
     *
     * @return void
     *
     * @throws \RuntimeException When an unknown file type has been encountered.
     */
    private function moveFile(SplFileInfo $file, $targetDir, $logging, $ioHandler)
    {
        $pathName        = $file->getPathname();
        $destinationFile = str_replace($this->tempDir, $targetDir, $pathName);

        // Symlink must(!) be handled first as the isDir() and isFile() checks return true for symlinks.
        if ($file->isLink()) {
            $target = $file->getLinkTarget();
            if ($logging) {
                $ioHandler->write(sprintf('link %s to %s', $target, $destinationFile));
            }
            symlink($target, $destinationFile);
            unlink($pathName);

            return;
        }

        if ($file->isDir()) {
            $permissions     = substr(decoct(fileperms($pathName)), 1);
            $this->folders[] = $pathName;
            if (!is_dir($destinationFile)) {
                if ($logging) {
                    $ioHandler->write(sprintf('mkdir %s (permissions: %s)', $pathName, $permissions));
                }
                mkdir($destinationFile, octdec($permissions), true);
            }

            return;
        }

        if ($file->isFile()) {
            $permissions = substr(decoct(fileperms($pathName)), 1);
            if ($logging) {
                $ioHandler->write(
                    sprintf('move %s to %s (permissions: %s)', $pathName, $destinationFile, $permissions)
                );
            }
            copy($pathName, $destinationFile);
            chmod($destinationFile, octdec($permissions));
            unlink($pathName);

            return;
        }

        throw new \RuntimeException(
            sprintf(
                'Unknown file of type %s encountered for %s',
                filetype($pathName),
                $pathName
            )
        );
    }

    /**
     * Check if we may install into the destination directory.
     *
     * @return bool
     */
    private function mayInstall()
    {
        $destinationDir = $this->file->get(self::SETTING_DESTINATION_DIR) . DIRECTORY_SEPARATOR;

        return !(file_exists($destinationDir . 'composer.json'));
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
