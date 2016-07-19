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

namespace Tenside\Core\SelfTest\Cli;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Tenside\Core\SelfTest\AbstractSelfTest;

/**
 * This class tests that a valid php-cli binary is available.
 */
class SelfTestCliRuntime extends AbstractSelfTest
{
    /**
     * The output buffer to keep track of the detection.
     *
     * @var BufferedOutput
     */
    private $log;

    /**
     * Check that we have a correct CLI executable of PHP.
     *
     * @return void
     */
    public function doTest()
    {
        $this->setMessage('Check if a valid PHP CLI executable is available.');

        $this->log = new BufferedOutput();

        // Prefer compile time configuration over path environment.
        if ($this->isBinaryAvailableFromConstants()) {
            return;
        }

        if ($this->isBinaryAvailableInPath()) {
            return;
        }

        $this->markFailed(
            'Could not find any PHP CLI executable, running tenside tasks will not work. ' . $this->log->fetch()
        );
    }

    /**
     * Check if any usable php executable is available from the internal constants.
     *
     * @return bool
     */
    private function isBinaryAvailableFromConstants()
    {
        // Prefer the cli version of the running php instance.
        if (('' === PHP_BINARY) || !file_exists(PHP_BINARY)) {
            return false;
        }

        return $this->isAnyBinaryValid([PHP_BINARY]);
    }

    /**
     * Check if any php executable is in the path.
     *
     * @return bool
     */
    private function isBinaryAvailableInPath()
    {
        $paths = array_filter(array_map('trim', explode(PATH_SEPARATOR, getenv('PATH'))));
        if (empty($paths)) {
            $paths = $this->getDefaultPaths();
        }

        // Prefer the cli version of the running php instance.
        if ((PHP_BINDIR !== '') && is_dir(PHP_BINDIR)) {
            array_unshift($paths, PHP_BINDIR);
        }

        return $this->isAnyBinaryValid($this->findBinaries($paths));
    }

    /**
     * Test the passed binaries.
     *
     * @param string[] $binaries The binaries to test.
     *
     * @return bool
     */
    private function isAnyBinaryValid($binaries)
    {
        foreach ($binaries as $binary) {
            if ($version = $this->testCliRuntime($binary)) {
                if (version_compare($version, '5.6', '<')) {
                    $this->log->writeln(sprintf('%s version is too low (%s)', $binary, $version));

                    return false;
                }

                $this->markSuccess('Found ' . $binary . ' (Version: ' . $version . ')');
                $this->getAutoConfig()->setPhpCliBinary($binary);

                return true;
            }
        }

        return false;
    }

    /**
     * Search all php binaries from the passed paths.
     *
     * @param string[] $paths     The paths to scan for binaries.
     *
     * @param string[] $fileNames Optional names of files to search for.
     *
     * @return string[]
     */
    private function findBinaries($paths, $fileNames = ['php', 'php.exe'])
    {
        // We have to work around the problem that the symfony Finder will try to follow the symlink when a file
        // i.e. /var/bin/foo is symlinked to /usr/bin/foo and therefore raise a warning that /var/bin is not in
        // the open_basedir locations.
        // Therefore we can not use the Finder component when open_basedir has been set.
        if ($baseDirs = array_filter(array_map('trim', explode(PATH_SEPARATOR, ini_get('open_basedir'))))) {
            $foundBinaries = [];

            foreach ($this->filterBaseDir($paths, $baseDirs) as $path) {
                foreach (scandir($path) as $file) {
                    if (in_array(basename($file), $fileNames)) {
                        $foundBinaries[] = new \SplFileInfo($path . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }

            return $foundBinaries;
        }

        if (empty($paths)) {
            return [];
        }

        $finder = new Finder();
        $finder->ignoreUnreadableDirs()->in($paths);

        foreach ($fileNames as $name) {
            $finder->name($name);
        }

        $foundBinaries = [];
        foreach ($finder as $file) {
            /** @var \SplFileInfo $file */
            $foundBinaries[] = $file->getPathname();
        }

        return $foundBinaries;
    }

    /**
     * Filter out the paths not covered by basedir.
     *
     * @param string[] $paths    The paths to filter.
     *
     * @param string[] $baseDirs The base dir paths.
     *
     * @return string[]
     */
    private function filterBaseDir($paths, $baseDirs)
    {
        return array_filter($paths, function ($path) use ($baseDirs) {
            foreach ($baseDirs as $baseDir) {
                if (substr($baseDir, 0, strlen($path)) === $path) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Test the cli runtime for a valid version string and return either the version or null.
     *
     * @param string $binary The binary to test.
     *
     * @return null|string
     */
    private function testCliRuntime($binary)
    {
        $process = new Process(
            sprintf(
                '%s %s',
                escapeshellcmd($binary),
                escapeshellarg('--version')
            )
        );

        if (0 !== $process->run()) {
            return null;
        }

        // Version PHP 5.4.45-0+deb7u1
        if (!preg_match('#.*PHP ([0-9a-zA-Z\.\-\+\~]+) \(cli\)#', $process->getOutput(), $output)) {
            return null;
        }

        return $output[1];
    }

    /**
     * Retrieve the list of default paths for the current OS.
     *
     * @return string[]
     */
    private function getDefaultPaths()
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return [
                'C:\php',
                'C:\php5'
            ];
        }

        return [
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
        ];
    }
}
