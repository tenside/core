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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * This class converts exceptions into proper responses.
 */
class ExceptionListener
{
    /**
     * The exception logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create a new instance.
     *
     * @param LoggerInterface $logger The logger.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
        $response  = null;

        switch (true) {
            case ($exception instanceof NotFoundHttpException):
                $response = $this->createNotFoundResponse($event->getRequest(), $exception);
                break;
            case ($exception instanceof AccessDeniedHttpException):
            case ($exception instanceof UnauthorizedHttpException):
            case ($exception instanceof BadRequestHttpException):
            case ($exception instanceof ServiceUnavailableHttpException):
            case ($exception instanceof NotAcceptableHttpException):
            case ($exception instanceof HttpException):
                /** @var HttpException $exception */
                $response = $this->createHttpExceptionResponse($exception);
                break;
            case ($exception instanceof AuthenticationCredentialsNotFoundException):
                $response = $this->createUnauthenticatedResponse($exception);
                break;
            default:
        }

        if (null === $response) {
            $response = $this->createInternalServerError($exception);
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
     * Create a http response.
     *
     * @param AuthenticationCredentialsNotFoundException $exception The exception to create a response for.
     *
     * @return JsonResponse
     */
    private function createUnauthenticatedResponse(AuthenticationCredentialsNotFoundException $exception)
    {
        return new JsonResponse(
            [
                'status'  => 'ERROR',
                'message' => $exception->getMessageKey()
            ],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Create a 500 response.
     *
     * @param \Exception $exception The exception to log.
     *
     * @return JsonResponse
     */
    private function createInternalServerError(\Exception $exception)
    {
        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->logger->error($message, array('exception' => $exception));

        return new JsonResponse(
            [
                'status'  => 'ERROR',
                'message' => JsonResponse::$statusTexts[JsonResponse::HTTP_INTERNAL_SERVER_ERROR]
            ],
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
