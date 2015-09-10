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

namespace Tenside\CoreBundle\Controller;

use Composer\IO\BufferIO;
use Composer\Util\ConfigValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for manipulating the composer.json file.
 */
class ComposerJsonController extends AbstractController
{
    /**
     * Retrieve the composer.json.
     *
     * @return Response
     */
    public function getComposerJsonAction()
    {
        return new Response($this->get('tenside.composer_json'));
    }

    /**
     * Update the composer.json with the given data if it is valid.
     *
     * @param Request $request The request to process.
     *
     * @return JsonResponse
     */
    public function putComposerJsonAction(Request $request)
    {
        $errors = $this->checkComposerJson($request->getContent());

        if (!empty($errors['error'])) {
            $errors['status'] = 'ERROR';
        } else {
            $errors['status'] = 'OK';

            $file = $this->get('tenside.composer_json');
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
     * @return array<string,string[]>
     */
    private function checkComposerJson($content)
    {
        // FIXME: make this configurable.
        $tempFile = sys_get_temp_dir() . '/composer.json.tmp';
        file_put_contents($tempFile, $content);

        $validator = new ConfigValidator(new BufferIO());

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
