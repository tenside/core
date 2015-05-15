<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/core
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @filesource
 */

namespace Tenside\Compiler;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Compiler;
use Tenside\Web\Application;

/**
 * This Compiler task adds all content from tenside/core into the phar.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class CoreTask extends AbstractTask
{
    /**
     * {@inheritDoc}
     */
    public function compile()
    {
        $this->embeddComposer();
        $this->embeddTensideCore();
        $this->setStub($this->getStub());
    }

    /**
     * Override in derived compilers to mangle certain files.
     *
     * @param string $path    The file being added.
     *
     * @param string $content The file content.
     *
     * @return string
     */
    protected function alterFileContents($path, $content)
    {
        if ($path === 'src/Composer/Composer.php') {
            $version = $this->getVersionInformationFor('composer/composer', 'version');
            $content = str_replace('@package_version@', $version['version'], $content);
            $content = str_replace('@package_branch_alias_version@', $version['version'], $content);
            $content = str_replace('@release_date@', $version['date'], $content);
        }

        if ($path === 'src/Tenside.php') {
            $version = $this->getVersionInformationFor('tenside/core', 'version');
            $content = str_replace('@package_version@', $version['version'], $content);
            $content = str_replace('@package_branch_alias_version@', $version['branch-alias'], $content);
            $content = str_replace('@release_date@', $version['date'], $content);
        }

        return $content;
    }

    /**
     * Calculate the routes as static lookup map.
     *
     * @return void
     */
    private function buildRouteMatcher()
    {
        $application = new Application();
        $routes      = new RouteCollection();
        $application->addRoutes($routes);

        $dumper = new PhpMatcherDumper($routes);
        $this->addFileContent('src/TensideUrlMatcher.php', $dumper->dump(['class' => '\Tenside\TensideUrlMatcher']));
    }

    /**
     * Embedd all stuff needed by composer into the phar.
     *
     * @return void
     */
    private function embeddComposer()
    {
        $composerDir = $this->getComposerDir();
        $vendorDir   = $this->getVendorDir();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->in($composerDir . '/src');

        foreach ($finder as $file) {
            $this->addFile($file);
        }
        $this->addFile(new \SplFileInfo($composerDir . '/src/Composer/Autoload/ClassLoader.php'), false);

        $finder = new Finder();
        $finder->files()
            ->name('*.json')
            ->in($composerDir . '/res');

        foreach ($finder as $file) {
            $this->addFile($file, false);
        }

        $this->addFile(new \SplFileInfo($composerDir . '/vendor/seld/cli-prompt/res/hiddeninput.exe'), false);

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->in($vendorDir . '/symfony/console')
            ->in($vendorDir . '/symfony/finder')
            ->in($vendorDir . '/symfony/process')
            ->in($vendorDir . '/seld/jsonlint/src/')
            ->in($vendorDir . '/seld/cli-prompt')
            ->in($vendorDir . '/justinrainbow/json-schema/src/');

        foreach ($finder as $file) {
            $this->addFile($file);
        }

        $this->addFile(new \SplFileInfo($composerDir.'/LICENSE'), false);
    }

    /**
     * Embedd all stuff needed by tenside core into the phar.
     *
     * @return void
     */
    private function embeddTensideCore()
    {
        $tensideDir = $this->getTensideCoreDir();
        $vendorDir  = $this->getVendorDir();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('stub.php')
            ->notName('app.php')
            ->in($tensideDir . '/src');
        foreach ($finder as $file) {
            $this->addFile($file);
        }

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->in($vendorDir . '/symfony/event-dispatcher')
            ->in($vendorDir . '/symfony/http-foundation')
            ->in($vendorDir . '/symfony/http-kernel')
            ->in($vendorDir . '/symfony/routing');
        foreach ($finder as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Detect the path to the composer root.
     *
     * @return string
     */
    private function getComposerDir()
    {
        return $this->getPackageRoot('composer/composer');
    }

    /**
     * Detect the path to the tenside core root.
     *
     * @return string
     *
     * @throws \RuntimeException When the directory can not be determined.
     */
    private function getTensideCoreDir()
    {
        return $this->getPackageRoot('tenside/core');
    }

    /**
     * Generate the phar stub.
     *
     * @return string
     */
    private function getStub()
    {
        $stub = file_get_contents(__DIR__ . '/../stub.php');
        // add warning once the phar is older than 30 days
        $warningTime = '';
        if (preg_match('{^[a-f0-9]+$}', $this->getVersionInformationFor('tenside/core', 'version'))) {
            $warningTime = 'define(\'TENSIDE_DEV_WARNING_TIME\', ' . (time() + 30 * 86400) . ');';
        }

        $stub = str_replace(
            [
                '// @@TENSIDE_DEV_WARNING_TIME@@',
                '@@TENSIDE_MIN_PHP_VERSION@@'
            ],
            [
                $warningTime,
                '5.4.0'
            ],
            $stub
        );

        return $stub;
    }
}
