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

use Composer\IO\BufferIO;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tenside\CoreBundle\Security\UserInformation;
use Tenside\CoreBundle\Security\UserInformationInterface;
use Tenside\Task\InstallTask;
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

        $installDir = $this->get('tenside.home')->homeDir();
        $inputData  = new JsonArray($request->getContent());
        $taskData   = new JsonArray();

        $taskData->set(InstallTask::SETTING_DESTINATION_DIR, $installDir);
        $taskData->set(InstallTask::SETTING_PACKAGE, $inputData->get('project/name'));
        if ($version = $inputData->get('project/version')) {
            $taskData->set(InstallTask::SETTING_VERSION, $version);
        }

        // Add tenside configuration.
        $tensideConfig = $this->get('tenside.config');
        $tensideConfig->set('secret', $inputData->get('credentials/secret'));

        // Add the user now.
        $user = new UserInformation([
            'username' => $inputData->get('credentials/username'),
            'acl'      => UserInformationInterface::ROLE_ALL
        ]);

        $user->set(
            'password',
            $this->get('security.password_encoder')->encodePassword($user, $inputData->get('credentials/password'))
        );

        $user = $this->get('tenside.user_provider')->addUser($user)->refreshUser($user);

        $taskId = $this->getTensideTasks()->queue('install', $taskData);

        try {
            $this->runInstaller($taskId);
        } catch (\Exception $e) {
            // Error starting the install task, roll back and output the error.
            $fileSystem = new Filesystem();
            $fileSystem->remove(
                array_map(
                    function ($file) use ($installDir) {
                        return $installDir . DIRECTORY_SEPARATOR . $file;
                    },
                    [
                        'tenside.json',
                        'tenside-tasks.json',
                        'tenside-task-' . $taskId . '.json',
                        'composer.json'
                    ]
                )
            );

            return new JsonResponse(
                [
                    'status'  => 'ERROR',
                    'message' => 'The install task could not be started.'
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(
            [
                'status' => 'OK',
                'task'   => $taskId,
                'token'  => $this->get('tenside.jwt_authenticator')->getTokenForData($user)
            ],
            JsonResponse::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('task_get', ['taskId' => $taskId], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    /**
     * This is an install time gateway to the task reader controller available only at install time.
     *
     * @param Request $request The current request.
     *
     * @return JsonResponse
     */
    public function getInstallationTaskProgressAction(Request $request)
    {
        // FIXME: this will be a bit of a problem as we are currently in the progress of installing everything.
        $this->checkUninstalled();

        return $this->forward('TensideCoreBundle:TaskRunner:getTask', $request->attributes, $request->query);
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
        // FIXME: need to determine this somehow better. Can not check for tenside also as we need the secret and user.
        if (file_exists($this->get('tenside.home')->homeDir() . DIRECTORY_SEPARATOR . 'composer.json')) {
            throw new NotAcceptableHttpException('Already installed in ' . $this->get('tenside.home')->homeDir());
        }
    }

    /**
     * Run the given task and return a response when an error occurred or null if it worked out.
     *
     * @param string $taskId The task id.
     *
     * @return void
     *
     * @throws \RuntimeException When the process could not be started.
     */
    private function runInstaller($taskId)
    {
        $runnerResponse = $this->forward('TensideCoreBundle:TaskRunner:run');

        $runnerStarted = json_decode($runnerResponse->getContent(), true);
        if ($runnerStarted['status'] !== 'OK' || $runnerStarted['task'] !== $taskId) {
            throw new \RuntimeException('Status was not ok');
        }
    }
}
