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
 * @author     Nico Schneider <nico.tcap@gmail.com>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\CoreBundle\Controller;

use Composer\Command\CreateProjectCommand;
use Composer\IO\BufferIO;
use Composer\IO\ConsoleIO;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Tenside\CoreBundle\Security\UserInformation;
use Tenside\CoreBundle\Security\UserInformationInterface;
use Tenside\Util\JsonArray;

/**
 * Controller for manipulating the composer.json file.
 */
class InstallProjectController extends AbstractController
{
    // FIXME: add an endpoint to check the system. See Selftest namespace.

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
        $config = $this->getTensideConfig();
        $config->set('secret', $data->get('credentials/secret'));

        // Add the user now.
        $user = new UserInformation([
            'username' => $data->get('credentials/username'),
            'acl'      => UserInformationInterface::ROLE_ALL
        ]);

        $user->set(
            'password',
            $this->get('security.password_encoder')->encodePassword($user, $data->get('credentials/password'))
        );

        $this->get('tenside.user_provider')->addUser($user);

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
        $url     = sprintf('https://packagist.org/packages/%s/%s.json', $vendor, $project);
        $rfs     = new RemoteFilesystem(new BufferIO());
        $results = $rfs->getContents($url, $url);
        $data    = new JsonArray($results);

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
        $tmpDir = tempnam($this->getTensideHome(), 'install');

        // If the temporary folder could not be created, error out.
        if (false === $tmpDir) {
            return [
                'status'   => 'ERROR',
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

        $realComposer = getenv('COMPOSER');
        $prevCwd      = getcwd();
        $this->setEnvironmentVariable($destination . '/composer.json');
        chdir($destination);

        try {
            $command->run($input, $output);
        } catch (\Exception $exception) {
            $this->setEnvironmentVariable($realComposer);
            chdir($prevCwd);
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->setEnvironmentVariable($realComposer);
        chdir($prevCwd);
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
                'messages' => [$exception->getMessage()]
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
        $destinationDir = $this->getTensideHome();
        $folders        = [$tmpDir];
        foreach (Finder::create()->in($tmpDir)->ignoreDotFiles(false) as $file) {
            /** @var SplFileInfo $file */
            $destinationFile = str_replace($tmpDir, $destinationDir, $file->getPathName());
            $permissions     = substr(decoct(fileperms($file->getPathName())), 1);

            if ($file->isDir()) {
                $folders[] = $file->getPathname();
                if (!is_dir($destinationFile)) {
                    mkdir($destinationFile, octdec($permissions), true);
                    $messages[] = sprintf(
                        'mkdir %s %s',
                        $file->getPathname(),
                        octdec($permissions)
                    );
                }
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
            'status'   => 'OK',
            'messages' => $messages
        ];
    }

    /**
     * Set or clear the composer related environment variables.
     *
     * @param false|string $realComposer The value for the COMPOSER environment variable.
     *
     * @return void
     */
    private function setEnvironmentVariable($realComposer)
    {
        putenv('COMPOSER=' . $realComposer ?: '');
    }
}
