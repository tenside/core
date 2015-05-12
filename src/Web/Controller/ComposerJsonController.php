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

use Composer\Util\ConfigValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tenside\Web\Auth\UserInformationInterface;

/**
 * Controller for manipulating the composer.json file.
 */
class ComposerJsonController extends AbstractRestrictedController
{
    /**
     * {@inheritdoc}
     */
    public static function createRoutes(RouteCollection $routes)
    {
        static::createRoute($routes, 'getComposerJson', '/api/v1/composer.json', ['GET']);
        static::createRoute($routes, 'putComposerJson', '/api/v1/composer.json', ['PUT']);
    }

    /**
     * Retrieve the composer.json.
     *
     * @return Response
     */
    public function getComposerJsonAction()
    {
        $this->needAccessLevel(UserInformationInterface::ACL_EDIT_COMPOSER_JSON);

        return new Response($this->getTenside()->getComposerJson());
    }

    /**
     * Update the composer.json with the given data if it is valid.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     */
    protected function putComposerJsonAction(Request $request)
    {
        $this->needAccessLevel(UserInformationInterface::ACL_EDIT_COMPOSER_JSON);

        $errors = $this->checkComposerJson($request->getContent());

        if (!empty($errors['error'])) {
            $errors['status'] = 'ERROR';
        } else {
            $errors['status'] = 'OK';

            $file = $this->getTenside()->getComposerJson();
            $file->load($request->getContent());
            $file->save();
        }

        return new JsonResponse($errors);
    }

    /**
     * Check the json contents and return the error array.
     *
     * @param string $content The Json content.
     *
     * @return array
     */
    private function checkComposerJson($content)
    {
        $tempFile = $this->getTenside()->getTempDir() . '/composer.json.tmp';
        file_put_contents($tempFile, $content);

        $validator = new ConfigValidator($this->getInputOutputHandler());

        list($errors, $publishErrors, $warnings) = $validator->validate($tempFile);
        unlink($tempFile);

        $errors = array_merge($errors, $publishErrors);

        $errors   = str_replace(dirname($tempFile), '', $errors);
        $warnings = str_replace(dirname($tempFile), '', $warnings);

        return array(
            'error'   => $errors,
            'warning' => $warnings,
        );
    }
}
