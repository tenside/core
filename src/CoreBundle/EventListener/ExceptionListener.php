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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $class     = get_class($exception);
        $response  = null;
        switch ($class) {
            case 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException':
                $response = $this->createNotFoundResponse($event->getRequest());
                break;
            case 'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\UnauthorizedHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\InternalServerErrorHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\ServiceUnavailableHttpException':
            case 'Symfony\\Component\\HttpKernel\\Exception\\HttpException':
                /** @var HttpException $exception */
                $response = $this->createHttpExceptionResponse($exception);
                break;
            default:
                return;
        }

        if (null === $response) {
            $response = $this->createInternalServerError();
        }

        $event->setResponse($response);
    }

    /**
     * Create a 404 response.
     *
     * @param Request $request The http request.
     *
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function createNotFoundResponse($request)
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_NOT_FOUND] . $request->getRequestUri(),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Create a 401 response.
     *
     * @param HttpException $exception The exception to create a response for.
     *
     * @return Response
     */
    private function createHttpExceptionResponse(HttpException $exception)
    {
        return new Response(
            Response::$statusTexts[$exception->getStatusCode()],
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Create a 500 response.
     *
     * @return Response
     */
    private function createInternalServerError()
    {
        return new Response(
            Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
