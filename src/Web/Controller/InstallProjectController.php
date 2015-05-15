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

namespace Tenside\Web\Controller;

use Composer\Command\CreateProjectCommand;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Util\JsonArray;
use Tenside\Web\Auth\AuthorizationFromConfig;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Controller for manipulating the composer.json file.
 */
class InstallProjectController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'createProject', '/install/create-project', ['PUT']);
        static::createRoute(
            $routes,
            'getProjectVersions',
            '/install/search-project/{vendor}/{package}',
            ['GET'],
            [
                'vendor'  => '[\-\_a-zA-Z]*',
                'package' => '[\-\_a-zA-Z]*'
            ]
        );
        // FIXME: add an endpoint to check the system. See Selftest namespace.
    }

    /**
     * Create a project.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     */
    public function createProjectAction(Request $request)
    {
        $this->checkUninstalled();

        $data = new JsonArray($request->getContent());
        // FIXME: check the input content.

        // Install project.
        $installMessages = $this->performInstall($data->get('project/name'), $data->get('project/version'));

        // FIXME: we should rather use exceptions for error results and a generic output buffer here.
        if ('OK' !== $installMessages['status']) {
            return new JsonResponse($installMessages);
        }

        // Add tenside configuration.
        $config = $this->getTenside()->getConfigSource();
        $config->set('secret', $data->get('credentials/secret'));

        // Add the user now.
        $users = new AuthorizationFromConfig($config);
        $users->addUser(
            $data->get('credentials/username'),
            $data->get('credentials/password'),
            UserInformationInterface::ACL_ALL
        );

        return new JsonResponse($installMessages);
    }

    /**
     * Create a project.
     *
     * @param string $vendor  The vendor name of the package.
     *
     * @param string $project The name of the package.
     *
     * @return JsonResponse
     */
    public function getProjectVersionsAction($vendor, $project)
    {
        $this->checkUninstalled();

        // FIXME: we only search the packagist API here.

        $results = $this->getTenside()->download(
            sprintf('https://packagist.org/packages/%s/%s.json', $vendor, $project)
        );

        $data = new JsonArray($results);

        return new JsonResponse(
            [
                'status' => 'OK',
                'versions' => $data->get('package/versions')
            ]
        );
    }

    /**
     * Ensure that we are not installed yet.
     *
     * @return void
     *
     * @throws NotAcceptableHttpException When the installation is already complete.
     */
    private function checkUninstalled()
    {
        if ($this->getTenside()->isInstalled()) {
            throw new NotAcceptableHttpException('Already installed');
        }
    }

    /**
     * Prepare a temporary directory.
     *
     * @return string
     *
     * @throws \RuntimeException When an error occurred.
     */
    private function prepareTmpDir()
    {
        $tmpDir = tempnam($this->getTenside()->getHomeDir(), 'install');

        // If the temporary folder could not be created, error out.
        if (false === $tmpDir) {
            return [
                'status' => 'ERROR',
                'messages' => ['Could not create the temporary directory']
            ];
        }
        unlink($tmpDir);
        mkdir($tmpDir, 0700);

        // FIXME: ensure the directory now exists and is writable etc.

        return $tmpDir;
    }

    /**
     * Fetch the project into the given directory.
     *
     * @param string          $package     The package name.
     *
     * @param string          $version     The version to install.
     *
     * @param string          $destination The target directory to install to.
     *
     * @param OutputInterface $output      The output to use.
     *
     * @return void
     *
     * @throws \RuntimeException When an error occurred.
     */
    private function fetchProject($package, $version, $destination, $output)
    {
        $command = new CreateProjectCommand();
        $input   = new ArrayInput(
            [
                'package'      => $package,
                'version'      => $version,
                'directory'    => $destination,
                '--no-install' => true
            ]
        );
        $input->setInteractive(false);
        $command->setIO(new ConsoleIO($input, $output, new HelperSet([])));

        // FIXME: hacking the home should not be necessary anymore as we use COMPOSER variable now.
        $realHome     = getenv('COMPOSER_HOME');
        $realComposer = getenv('COMPOSER');
        putenv('COMPOSER_HOME=');
        putenv('COMPOSER=' . $destination . '/composer.json');
        chdir($destination);
        try {
            $command->run($input, $output);
        } catch (\Exception $exception) {
            putenv('COMPOSER_HOME=' . $realHome);
            putenv('COMPOSER=' . $realComposer);
            chdir($realHome);
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
        putenv('COMPOSER_HOME=' . $realHome);
        putenv('COMPOSER=' . $realComposer);
        chdir($realHome);
    }

    /**
     * Install a package as root project.
     *
     * @param string $package The package name.
     *
     * @param string $version The version to install.
     *
     * @return array
     */
    private function performInstall($package, $version)
    {
        try {
            $tmpDir = $this->prepareTmpDir();
        } catch (\RuntimeException $exception) {
            return [
                'status'   => 'ERROR',
                'messages' => [ $exception->getMessage()]
            ];
        }

        $output = new BufferedOutput();

        try {
            $this->fetchProject($package, $version, $tmpDir, $output);
        } catch (\Exception $exception) {
            return [
                'status'   => 'ERROR',
                'messages' => [$exception->getMessage()]
            ];
        }

        $messages = explode(PHP_EOL, $output->fetch());

        // Ensure we have the file permissions not in cache as new files were installed.
        clearstatcache();
        // Now move all the files over.
        $destinationDir = $this->getTenside()->getHomeDir();
        $folders        = [$tmpDir];
        foreach (Finder::create()->in($tmpDir)->ignoreDotFiles(false) as $file) {
            /** @var SplFileInfo $file */
            $destinationFile = str_replace($tmpDir, $destinationDir, $file->getPathName());
            $permissions     = substr(decoct(fileperms($file->getPathName())), 1);

            if ($file->isDir()) {
                mkdir($destinationFile, octdec($permissions));
                $folders[]  = $file->getPathname();
                $messages[] = sprintf(
                    'mkdir %s %s',
                    $file->getPathname(),
                    octdec($permissions)
                );
            } else {
                copy($file->getPathname(), $destinationFile);
                chmod($destinationFile, octdec($permissions));
                unlink($file->getPathname());
                $messages[] = sprintf(
                    'move %s to %s',
                    $file->getPathname(),
                    $destinationFile
                );
            }
        }

        foreach (array_reverse($folders) as $folder) {
            rmdir($folder);
            $messages[] = sprintf('remove directory %s', $folder);
        }

        return [
            'status' => 'OK',
            'messages' => $messages
        ];
    }
}
