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

use Tenside\Compiler;

/**
 * Abstract base for compile tasks.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class AbstractTask
{
    /**
     * The compiler.
     *
     * @var Compiler
     */
    private $compiler;

    /**
     * Retrieve the compiler.
     *
     * @return Compiler
     */
    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Set the compiler.
     *
     * @param Compiler $compiler The compiler this task shall work on.
     *
     * @return AbstractTask
     */
    public function setCompiler($compiler)
    {
        $this->compiler = $compiler;

        return $this;
    }

    /**
     * Run the compile task.
     *
     * @return void
     */
    abstract public function compile();

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
        $this->compiler->error($message, $context);
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
        $this->compiler->warning($message, $context);
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
        $this->compiler->notice($message, $context);
    }

    /**
     * Override in derived compilers to mangle certain files.
     *
     * @param string $path    The file being added.
     *
     * @param string $content The file content.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function alterFileContents($path, $content)
    {
        return $content;
    }

    /**
     * Add a single file to the phar.
     *
     * @param \SplFileInfo $file         The file to add.
     *
     * @param bool         $strip        Boolean flag if whitespace shall be stripped.
     *
     * @param string|null  $pathOverride If given, the passed path value will get used instead of the source path.
     *
     * @return void
     */
    protected function addFile($file, $strip = true, $pathOverride = null)
    {
        if ($pathOverride) {
            $path = $pathOverride;
        } else {
            $path = strtr(
                str_replace(dirname($this->getVendorDir()) . DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                '\\',
                '/'
            );
        }

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        $this->addFileContent($path, $this->alterFileContents($path, $content));
    }

    /**
     * Add a single file to the phar.
     *
     * @param \SplFileInfo $file         The file to add.
     *
     * @param string|null  $pathOverride If given, the passed path value will get used instead of the source path.
     *
     * @return void
     */
    protected function addFileRaw($file, $pathOverride = null)
    {
        if ($pathOverride) {
            $path = $pathOverride;
        } else {
            $path = strtr(
                str_replace(dirname($this->getVendorDir()).DIRECTORY_SEPARATOR, '', $file->getRealPath()),
                '\\',
                '/'
            );
        }

        $content = file_get_contents($file);
        $this->addFileContent($path, $content);
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
    public function addFileContent($path, $content)
    {
        $this->compiler->addFile($path, $content);
    }

    /**
     * Set the stub.
     *
     * @param string $stub The stub content.
     *
     * @return void
     */
    public function setStub($stub)
    {
        $this->compiler->setStub($stub);
    }

    /**
     * Detect the path to the vendor root.
     *
     * @return string
     *
     * @throws \RuntimeException When the directory can not be determined.
     */
    protected function getVendorDir()
    {
        return $this->compiler->getVendorDir();
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
    protected function getPackageRoot($packageName)
    {
        return $this->compiler->getPackageRoot($packageName);
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
        return $this->compiler->getVersionInformationFor($packageName, $fieldName);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string.
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);

                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}
