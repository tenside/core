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
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\CoreBundle\Controller;

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
    /**
     * Create a project.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     */
    public function createProjectAction(Request $request)
    {
        // FIXME: We definately should split this here up into config method and installation method which is already
        // auth'ed.
        $this->checkUninstalled();
        $status = $this->get('tenside.status');
        $result = [];
        $header = [];

        $installDir = $this->get('tenside.home')->homeDir();
        $dataDir    = $this->get('tenside.home')->tensideDataDir();
        $inputData  = new JsonArray($request->getContent());
        $taskData   = new JsonArray();

        $taskData->set(InstallTask::SETTING_DESTINATION_DIR, $installDir);
        $taskData->set(InstallTask::SETTING_PACKAGE, $inputData->get('project/name'));
        if ($version = $inputData->get('project/version')) {
            $taskData->set(InstallTask::SETTING_VERSION, $version);
        }

        if (!$status->isTensideConfigured()) {
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

            $user            = $this->get('tenside.user_provider')->addUser($user)->refreshUser($user);
            $result['token'] = $this->get('tenside.jwt_authenticator')->getTokenForData($user);
        }

        if (!$status->isProjectPresent() && !$status->isProjectInstalled()) {
            $taskId             = $this->getTensideTasks()->queue('install', $taskData);
            $result['task']     = $taskId;
            $header['Location'] = $this->generateUrl(
                'task_get',
                ['taskId' => $taskId],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            try {
                $this->runInstaller($taskId);
            } catch (\Exception $e) {
                // Error starting the install task, roll back and output the error.
                $fileSystem = new Filesystem();
                $fileSystem->remove($installDir . DIRECTORY_SEPARATOR . 'composer.json');
                $fileSystem->remove(
                    array_map(
                        function ($file) use ($dataDir) {
                            return $dataDir . DIRECTORY_SEPARATOR . $file;
                        },
                        [
                            'tenside.json',
                            'tenside.json~',
                            'tenside-tasks.json',
                            'tenside-task-' . $taskId . '.json',
                            'tenside-task-' . $taskId . '.json~'
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
        }

        return new JsonResponse(
            $result,
            JsonResponse::HTTP_CREATED,
            $header
        );
    }

    /**
     * This is a gateway to the self test controller available only at install time.
     *
     * @return JsonResponse
     */
    public function getSelfTestAction()
    {
        $this->checkUninstalled();

        return $this->forward('TensideCoreBundle:SelfTest:getAllTests');
    }

    /**
     * This is a gateway to the auto config controller available only at install time.
     *
     * @return JsonResponse
     */
    public function getAutoConfigAction()
    {
        $this->checkUninstalled();

        return $this->forward('TensideCoreBundle:SelfTest:getAutoConfig');
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
        $rfs     = new RemoteFilesystem($this->getInputOutput());
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
     * Check if installation is new, partial or complete.
     *
     * @return JsonResponse
     */
    public function getInstallationStateAction()
    {
        $status = $this->get('tenside.status');

        return new JsonResponse(
            [
                'state'  => [
                    'tenside_configured' => $status->isTensideConfigured(),
                    'project_created'    => $status->isProjectPresent(),
                    'project_installed'  => $status->isProjectInstalled(),
                ],
                'status' => 'OK'
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
        if ($this->get('tenside.status')->isComplete()) {
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
