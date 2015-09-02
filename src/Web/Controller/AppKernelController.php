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

namespace Tenside\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Controller for manipulating the AppKernel file.
 */
class AppKernelController extends AbstractRestrictedController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'getAppKernel', '/api/v1/AppKernel.php', ['GET']);
        static::createRoute($routes, 'putAppKernel', '/api/v1/AppKernel.php', ['PUT']);
    }

    /**
     * Retrieve the composer.json.
     *
     * @return Response
     */
    public function getAppKernelAction()
    {
        $this->needAccessLevel(UserInformationInterface::ACL_EDIT_APPKERNEL);

        return new Response(file_get_contents($this->getAppKernelPath()));
    }

    /**
     * Update the AppKernel with the given data if it is valid.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     */
    protected function putAppKernelAction(Request $request)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_EDIT_COMPOSER_JSON);

        $errors = $this->checkAppKernel($request->getContent());

        if (!empty($errors['error'])) {
            $errors['status'] = 'ERROR';
        } else {
            $errors['status'] = 'OK';

            $this->saveAppKernel($request->getContent());
        }

        return new JsonResponse($errors);
    }

    /**
     * Check the contents and return the error array.
     *
     * @param string $content The PHP content.
     *
     * @return array<string,string[]>
     */
    private function checkAppKernel($content)
    {
        $phpCli = 'php';
        $config = $this->getTenside()->getConfigSource();
        if ($config->has('php-cli')) {
            $phpCli = $config->get('php-cli');
        }

        $process = new Process(escapeshellcmd($phpCli) . ' -l');
        $process->setInput($content);
        $process->run();

        if (!$process->isSuccessful()) {
            $output = $process->getErrorOutput();
            if ((bool) preg_match('/Parse error:\s*syntax error,(.+?)\s+in\s+.+?\s*line\s+(\d+)/', $output, $match)) {
                return [
                    'error' => [
                        'line' => (int) $match[2],
                        'msg'  => $match[1]
                    ]
                ];
            }

            // This might expose sensitive data but as we are in authenticated context, this is ok.
            return [
                'error' => [
                    'line' => '0',
                    'msg'  => $output
                ]
            ];
        }

        return ['error' => []];
    }

    /**
     * Retrieve the path to AppKernel.php
     *
     * @return string
     */
    private function getAppKernelPath()
    {
        return $this->getTenside()->getHomeDir() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AppKernel.php';
    }

    /**
     * Retrieve a file object for the AppKernel.php.
     *
     * @param string $content The PHP content.
     *
     * @return void
     */
    private function saveAppKernel($content)
    {
        $file = new \SplFileObject($this->getAppKernelPath(), 'r+');
        $file->ftruncate(0);
        $file->fwrite($content);
        unset($file);
    }
}
