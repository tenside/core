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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tenside\CoreBundle\Annotation\ApiDescription;
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
     * Configure tenside.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     *
     * @throws NotAcceptableHttpException When the configuration is already complete.
     *
     * @ApiDoc(
     *   section="install",
     *   statusCodes = {
     *     201 = "When everything worked out ok"
     *   },
     * )
     * @ApiDescription(
     *   request={
     *     "credentials" = {
     *       "description" = "The credentials of the admin user.",
     *       "children" = {
     *         "secret" = {
     *           "dataType" = "string",
     *           "description" = "The secret to use for encryption and signing.",
     *           "required" = true
     *         },
     *         "username" = {
     *           "dataType" = "string",
     *           "description" = "The name of the admin user.",
     *           "required" = true
     *         },
     *         "password" = {
     *           "dataType" = "string",
     *           "description" = "The password to use for the admin.",
     *           "required" = false
     *         }
     *       }
     *     },
     *     "configuration" = {
     *       "description" = "The credentials of the admin user.",
     *       "children" = {
     *         "php_cli" = {
     *           "dataType" = "string",
     *           "description" = "The PHP interpreter to run on command line."
     *         },
     *         "php_cli_arguments" = {
     *           "dataType" = "string",
     *           "description" = "Command line arguments to add."
     *         }
     *       }
     *     }
     *   },
     *   response={
     *     "token" = {
     *       "dataType" = "string",
     *       "description" = "The API token for the created user"
     *     }
     *   }
     * )
     */
    public function configureAction(Request $request)
    {
        if ($this->get('tenside.status')->isTensideConfigured()) {
            throw new NotAcceptableHttpException('Already configured.');
        }
        $inputData = new JsonArray($request->getContent());

        // Add tenside configuration.
        $tensideConfig = $this->get('tenside.config');
        $tensideConfig->set('secret', $inputData->get('credentials/secret'));

        if ($inputData->has('configuration/php_cli')) {
            $tensideConfig->set('php_cli', $inputData->get('configuration/php_cli'));
        }

        if ($inputData->has('configuration/php_cli_arguments')) {
            $tensideConfig->set('php_cli_arguments', $inputData->get('configuration/php_cli_arguments'));
        }

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

        return new JsonResponse(
            [
                'status' => 'OK',
                'token'  => $this->get('tenside.jwt_authenticator')->getTokenForData($user)
            ],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Create a project.
     *
     * @param Request $request The request.
     *
     * @return JsonResponse
     *
     * @throws NotAcceptableHttpException When the installation is already complete.
     *
     * @ApiDoc(
     *   section="install",
     *   statusCodes = {
     *     201 = "When everything worked out ok"
     *   },
     * )
     * @ApiDescription(
     *   request={
     *     "project" = {
     *       "description" = "The name of the project to install.",
     *       "children" = {
     *         "name" = {
     *           "dataType" = "string",
     *           "description" = "The name of the project to install.",
     *           "required" = true
     *         },
     *         "version" = {
     *           "dataType" = "string",
     *           "description" = "The name of the project to install.",
     *           "required" = false
     *         }
     *       }
     *     }
     *   },
     *   response={
     *     "task" = {
     *       "dataType" = "string",
     *       "description" = "The id of the created install task"
     *     }
     *   }
     * )
     */
    public function createProjectAction(Request $request)
    {
        $status = $this->get('tenside.status');
        if ($status->isProjectPresent() || $status->isProjectInstalled()) {
            throw new NotAcceptableHttpException('Already configured.');
        }

        $this->checkUninstalled();
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

        return new JsonResponse(
            [
                'status' => 'OK',
                'task'   => $taskId
            ],
            JsonResponse::HTTP_CREATED,
            $header
        );
    }

    /**
     * This is a gateway to the self test controller available only at install time.
     *
     * This is just here as the other route is protected with login.
     * This method is inaccessible as soon as the installation is complete.
     *
     * @return JsonResponse
     *
     * @ApiDoc(
     *   section="install",
     *   description="Install time - self test."
     * )
     * @ApiDescription(
     *   response={
     *     "results" = {
     *       "actualType" = "collection",
     *       "subType" = "object",
     *       "description" = "The test results.",
     *       "children" = {
     *         "name" = {
     *           "dataType" = "string",
     *           "description" = "The name of the test"
     *         },
     *         "state" = {
     *           "dataType" = "choice",
     *           "description" = "The test result state.",
     *           "format" = "[FAIL|SKIPPED|SUCCESS|WARNING]"
     *         },
     *         "message" = {
     *           "dataType" = "string",
     *           "description" = "The detailed message of the test result."
     *         },
     *         "explain" = {
     *           "dataType" = "string",
     *           "description" = "Optional description that could hint any problems and/or explain the error further."
     *         }
     *       }
     *     }
     *   }
     * )
     */
    public function getSelfTestAction()
    {
        $this->checkUninstalled();

        return $this->forward('TensideCoreBundle:SelfTest:getAllTests');
    }

    /**
     * Install time gateway to the auto config.
     *
     * This is just here as the other route is protected with login.
     * This method is inaccessible as soon as the installation is complete.
     *
     * @return JsonResponse
     *
     * @ApiDoc(
     *   section="install",
     *   description="Install time - auto config."
     * )
     * @ApiDescription(
     *   response={
     *     "php_cli" = {
     *       "dataType" = "string",
     *       "description" = "The PHP interpreter to run on command line."
     *     },
     *     "php_cli_arguments" = {
     *       "dataType" = "string",
     *       "description" = "Command line arguments to add."
     *     }
     *   }
     * )
     */
    public function getAutoConfigAction()
    {
        $this->checkUninstalled();

        return $this->forward('TensideCoreBundle:SelfTest:getAutoConfig');
    }

    /**
     * Retrieve the available versions of a package.
     *
     * @param string $vendor  The vendor name of the package.
     *
     * @param string $project The name of the package.
     *
     * @return JsonResponse
     *
     * @ApiDoc(
     *   section="install",
     *   statusCodes = {
     *     200 = "When everything worked out ok"
     *   }
     * )
     * @ApiDescription(
     *   response={
     *     "versions" = {
     *       "actualType" = "collection",
     *       "subType" = "object",
     *       "description" = "The list of versions",
     *       "children" = {
     *         "name" = {
     *           "dataType" = "string",
     *           "description" = "The name of the package"
     *         },
     *         "version" = {
     *           "dataType" = "string",
     *           "description" = "The version of the package"
     *         },
     *         "version_normalized" = {
     *           "dataType" = "string",
     *           "description" = "The normalized version of the package"
     *         },
     *         "reference" = {
     *           "dataType" = "string",
     *           "description" = "The optional reference"
     *         }
     *       }
     *     }
     *   }
     * )
     */
    public function getProjectVersionsAction($vendor, $project)
    {
        $this->checkUninstalled();

        // FIXME: we only search the packagist API here.
        $url     = sprintf('https://packagist.org/packages/%s/%s.json', $vendor, $project);
        $rfs     = new RemoteFilesystem($this->getInputOutput());
        $results = $rfs->getContents($url, $url);
        $data    = new JsonArray($results);

        $versions = [];

        foreach ($data->get('package/versions') as $information) {
            $version = [
                'name'               => $information['name'],
                'version'            => $information['version'],
                'version_normalized' => $information['version_normalized'],
            ];

            $normalized = $information['version'];
            if ('dev-' === substr($normalized, 0, 4)) {
                if (isset($information['extra']['branch-alias'][$normalized])) {
                    $version['version_normalized'] = $information['extra']['branch-alias'][$normalized];
                }
            }

            if (isset($information['source']['reference'])) {
                $version['reference'] = $information['source']['reference'];
            } elseif (isset($information['dist']['reference'])) {
                $version['reference'] = $information['dist']['reference'];
            }

            $versions[] = $version;
        }

        return new JsonResponse(
            [
                'status' => 'OK',
                'versions' => $versions
            ]
        );
    }

    /**
     * Check if installation is new, partial or complete.
     *
     * @return JsonResponse
     *
     * @ApiDoc(
     *   section="install",
     *   description="This method provides information about the installation.",
     *   authentication=false,
     *   statusCodes = {
     *     200 = "When everything worked out ok"
     *   }
     * )
     * @ApiDescription(
     *   response={
     *     "state" = {
     *       "children" = {
     *         "tenside_configured" = {
     *           "dataType" = "bool",
     *           "description" = "Flag if tenside has been completely configured."
     *         },
     *         "project_created" = {
     *           "dataType" = "bool",
     *           "description" = "Flag determining if a composer.json is present."
     *         },
     *         "project_installed" = {
     *           "dataType" = "bool",
     *           "description" = "Flag determining if the composer project has been installed (vendor present)."
     *         }
     *       }
     *     },
     *     "status" = {
     *       "dataType" = "string",
     *       "description" = "Either OK or ERROR"
     *     },
     *     "message" = {
     *       "dataType" = "string",
     *       "description" = "The API error message if any (only present when status is ERROR)"
     *     }
     *   }
     * )
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
