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

namespace Tenside\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This class converts exceptions into proper responses.
 */
class ExceptionListener
{
    /**
     * Maps known exceptions to HTTP exceptions.
     *
     * @param GetResponseForExceptionEvent $event The event object.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $class     = get_class($exception);
        $response  = null;
        switch ($class) {
            case 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException':
                $response = $this->createNotFoundResponse($event->getRequest(), $exception);
                break;
            case 'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\UnauthorizedHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\InternalServerErrorHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\ServiceUnavailableHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\NotAcceptableHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\HttpException':
                /** @var HttpException $exception */
                $response = $this->createHttpExceptionResponse($exception);
                break;
            default:
        }

        if (null === $response) {
            $response = $this->createInternalServerError();
        }

        $event->setResponse($response);
    }

    /**
     * Create a 404 response.
     *
     * @param Request    $request   The http request.
     *
     * @param \Exception $exception The exception.
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function createNotFoundResponse($request, $exception)
    {
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = 'Uri ' . $request->getRequestUri() . ' could not be found';
        }

        return new JsonResponse(
            [
                'status'  => 'ERROR',
                'message' => $message
            ],
            JsonResponse::HTTP_NOT_FOUND
        );
    }

    /**
     * Create a http response.
     *
     * @param HttpException $exception The exception to create a response for.
     *
     * @return JsonResponse
     */
    private function createHttpExceptionResponse(HttpException $exception)
    {
        return new JsonResponse(
            [
                'status'  => 'ERROR',
                'message' => $exception->getMessage()
            ],
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Create a 500 response.
     *
     * @return JsonResponse
     */
    private function createInternalServerError()
    {
        return new JsonResponse(
            [
                'status'  => 'ERROR',
                'message' => JsonResponse::$statusTexts[JsonResponse::HTTP_INTERNAL_SERVER_ERROR]
            ],
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
