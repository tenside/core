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

use Composer\Json\JsonFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Tenside\Compiler\AbstractTask;

/**
 * The Compiler class compiles tenside into a phar.
 */
class Compiler
{
    /**
     * The output interface.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The list of compile tasks to perform.
     *
     * @var AbstractTask[]
     */
    private $tasks;

    /**
     * The phar file we are working on.
     *
     * @var \Phar
     */
    private $phar;

    /**
     * The stub to use.
     *
     * @var string
     */
    private $stub;

    /**
     * Cache for keeping track of the installed versions of packages.
     *
     * @var array
     */
    private $versions;

    /**
     * Path to the vendor dir.
     *
     * @var string
     */
    private $vendorDir;

    /**
     * Path to the package root dirs.
     *
     * @var string[]
     */
    private $packageRoot;

    /**
     * Create a new instance.
     *
     * @param LoggerInterface $logger The logger instance to use.
     *
     * @param AbstractTask[]  $tasks  The task list to perform.
     */
    public function __construct($logger, $tasks = [])
    {
        $this->logger = $logger;

        foreach ($tasks as $task) {
            $this->addTask($task);
        }
    }

    /**
     * Add a compile task.
     *
     * @param AbstractTask $task The task to add.
     *
     * @return void
     */
    public function addTask(AbstractTask $task)
    {
        $task->setCompiler($this);
        $this->tasks[] = $task;
    }

    /**
     * Detect the path to the vendor root.
     *
     * @return string
     *
     * @throws \RuntimeException When the directory can not be determined.
     */
    public function getVendorDir()
    {
        if ($this->vendorDir) {
            return $this->vendorDir;
        }

        if (is_dir(__DIR__ . '/../../../../vendor')) {
            return $this->vendorDir = realpath(__DIR__ . '/../../../../vendor');
        }
        if (is_dir(__DIR__ . '/../vendor')) {
            return $this->vendorDir = realpath(__DIR__ . '/../vendor');
        }

        throw new \RuntimeException('Can not locate the vendor root.');
    }

    /**
     * Add files to the phar file.
     *
     * Override in derived compilers.
     *
     * @return void
     */
    protected function addFiles()
    {
    }

    /**
     * Compiles tenside into a single phar file.
     *
     * @param string $pharFile The full path to the file to create.
     *
     * @return void
     */
    public function compile($pharFile = 'tenside.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, basename($pharFile));
        $phar = $phar->convertToExecutable(\Phar::PHAR, \Phar::NONE);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $this->notice('building ' . realpath($pharFile));
        $phar->startBuffering();

        $this->phar = $phar;

        foreach ($this->tasks as $task) {
            $task->compile();
        }

        $this->addFiles();

        // Stub
        if (isset($this->stub)) {
            $phar->setStub($this->stub);
        }

        $phar->stopBuffering();

        // disabled for interoperability with systems without gzip ext
        $phar->compressFiles(\Phar::GZ);

        $phar->setMetadata(
            array(
                'license' => '/LICENSE'
            )
        );
        $this->notice('Total of ' . $phar->count() . ' files.');

        unset($phar);
    }

    /**
     * Add a single file to the phar.
     *
     * @param string $path    The pathname of the file to use.
     *
     * @param string $content The file content.
     *
     * @return void
     */
    public function addFile($path, $content)
    {
        if ($this->phar->offsetExists($path)) {
            $this->notice('Skipping already present file ' . $path);
            return;
        }

        $this->phar->addFromString($path, $content);
        $this->logfileSize(strlen($content), $path);
    }

    /**
     * Set the stub.
     *
     * @param string $stub The stub content.
     *
     * @return Compiler
     */
    public function setStub($stub)
    {
        if (isset($this->stub)) {
            $this->notice('Skipping already present stub.');
            return $this;
        }

        $this->stub = $stub;

        return $this;
    }

    /**
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param string $message The message to log.
     *
     * @param array  $context The optional context.
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
     *
     * @param string $message The message to log.
     *
     * @param array  $context The optional context.
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message The message to log.
     *
     * @param array  $context The optional context.
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Detect the path to the vendor root.
     *
     * @param string $packageName The package name.
     *
     * @return string
     *
     * @throws \RuntimeException When the directory can not be determined.
     */
    public function getPackageRoot($packageName)
    {
        if (isset($this->packageRoot[$packageName])) {
            return $this->packageRoot[$packageName];
        }

        $vendorDir = $this->getVendorDir();
        // Detect the root directory of the package.
        if (is_dir($vendorDir . '/' . $packageName)) {
            return $this->packageRoot[$packageName] = $vendorDir . '/' . $packageName;
        }

        // Second, check if it is the root package.
        $content = json_decode(file_get_contents(dirname($vendorDir) . '/composer.json'), true);
        if ($content['name'] === $packageName) {
            return $this->packageRoot[$packageName] = dirname($this->getVendorDir());
        }

        throw new \RuntimeException('Unable to determine package root of: ' . $packageName . ' is it installed?');
    }

    /**
     * Try to look up the version information for a given package.
     *
     * @param string      $packageName The package name.
     *
     * @param string|null $fieldName   The name of the version field to return or null to return the whole array
     *                                 (one of 'version', 'branch-alias', 'date').
     *
     * @return array|string
     */
    public function getVersionInformationFor($packageName, $fieldName = null)
    {
        if (!isset($this->versions[$packageName])) {
            $version = $this->getInformationFromInstalledJson($packageName);
            if (empty($version)) {
                $version = $this->loadVersionInformationFromGit($this->getPackageRoot($packageName));
            }
            $this->versions[$packageName] = $version;

            echo sprintf(
                'Detected: %s %s/%s (%s)' . PHP_EOL,
                $packageName,
                $version['version'],
                $version['branch-alias'],
                $version['date']
            );
        }

        if (null !== $fieldName) {
            return $this->versions[$packageName][$fieldName];
        }

        return $this->versions[$packageName];
    }

    /**
     * Read the package information for a certain package from the installed.json.
     *
     * @param string $packageName The package name to search.
     *
     * @return array|null
     *
     * @throws \RuntimeException When the installed.json can not be found.
     */
    private function getInformationFromInstalledJson($packageName)
    {
        $installedJson = $this->getVendorDir() . '/composer/installed.json';

        if (!is_file($installedJson)) {
            throw new \RuntimeException('installed.json not found. Is the installation complete?');
        }

        foreach (json_decode(file_get_contents($installedJson), true) as $package) {
            if ($package['name'] === $packageName) {
                return $this->convertInformationFromInstalledJson($package);
            }
        }

        return null;
    }

    /**
     * Convert the package information obtained from installed json into a version information array.
     *
     * @param array $information The package information from installed.json.
     *
     * @return array
     */
    private function convertInformationFromInstalledJson($information)
    {
        $version['date']    = $information['time'];
        $version['version'] = $information['version'];
        if (substr($version['version'], 0, 4) == 'dev-') {
            if (isset($information['source']['reference'])) {
                $version['version'] = $information['source']['reference'];
            } elseif (isset($information['dist']['reference'])) {
                $version['version'] = $information['dist']['reference'];
            }
        }
        if (isset($information['extra']['branch-alias']['dev-master'])) {
            $version['branch-alias'] = $information['extra']['branch-alias']['dev-master'];
        }

        return $version;
    }

    /**
     * Try to look up the version information for a given package.
     *
     * @param string $directory The home directory of the package.
     *
     * @return array
     *
     * @throws \RuntimeException When the git repository is invalid or git executable can not be run.
     */
    private function loadVersionInformationFromGit($directory)
    {
        $cwd = getcwd();
        chdir($directory);

        $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException(
                'Can\'t run git log in ' . $directory . '. ' .
                'Ensure to run compile from git repository clone and that git binary is available.'
            );
        }
        $version['version'] = trim($process->getOutput());

        $process = new Process('git log -n1 --pretty=%ci HEAD', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException(
                'Can\'t run git log in ' . $directory . '. ' .
                'Ensure to run compile from git repository clone and that git binary is available.'
            );
        }

        $date = new \DateTime(trim($process->getOutput()));
        $date->setTimezone(new \DateTimeZone('UTC'));
        $version['date'] = $date->format('Y-m-d H:i:s');

        $process = new Process('git describe --tags --exact-match HEAD');
        if ($process->run() == 0) {
            $version['version'] = trim($process->getOutput());
        } else {
            // get branch-alias defined in composer.json for dev-master (if any)
            $localConfig = $directory.'/composer.json';
            $file        = new JsonFile($localConfig);
            $localConfig = $file->read();
            if (isset($localConfig['extra']['branch-alias']['dev-master'])) {
                $version['branch-alias'] = $localConfig['extra']['branch-alias']['dev-master'];
            }
        }

        chdir($cwd);

        return $version;
    }

    /**
     * Log the file name and size of a file being added.
     *
     * @param int    $size     The file size in bytes.
     *
     * @param string $fileName The file name of the file being added.
     *
     * @return void
     */
    private function logfileSize($size, $fileName)
    {
        $units = ['Byte', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        for ($i = 0; $size > 1000; $i++) {
            $size /= 1000;
        }

        $this->notice(
            str_pad(number_format(round($size), 2) . ' ' . $units[$i], 20, '              ') . ' ' . $fileName
        );
    }
}
