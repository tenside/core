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

namespace Tenside\Test\CoreBundle\EventListener;

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
use Tenside\CoreBundle\EventListener\ExceptionListener;
use Tenside\Test\TestCase;

/**
 * Test the exception listener.
 */
class ExceptionListenerTest extends TestCase
{
    /**
     * Test that a NotFoundHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionNotFoundHttpException()
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getRequest')->willReturn(Request::create('https://example.com/'));
        $event->method('getException')->willReturn(new NotFoundHttpException('No cows here :/'));

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals('No cows here :/', $content['message']);
    }

    /**
     * Test that a NotFoundHttpException without message is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionNotFoundHttpExceptionWithoutMessage()
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getRequest')->willReturn(Request::create('https://example.com/'));
        $event->method('getException')->willReturn(new NotFoundHttpException());

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals('Uri / could not be found', $content['message']);
    }

    /**
     * Test that a AccessDeniedHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionAccessDeniedHttpException()
    {
        $exception = new AccessDeniedHttpException($message = 'Na, get away from my cheese!');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_FORBIDDEN, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * Test that a UnauthorizedHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionUnauthorizedHttpException()
    {
        $exception = new UnauthorizedHttpException('MOOH security!', $message = 'Say MOOH! to get in.');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * Test that a BadRequestHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionBadRequestHttpException()
    {
        $exception = new BadRequestHttpException($message = 'You appear to not have provided me with proper cheese.');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * Test that a ServiceUnavailableHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionServiceUnavailableHttpException()
    {
        $exception = new ServiceUnavailableHttpException(null, $message = 'We can not serve milk currently');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * Test that a NotAcceptableHttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionNotAcceptableHttpException()
    {
        $exception = new NotAcceptableHttpException($message = 'Cream cheese is not acceptable!');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * Test that a HttpException is rendered properly.
     *
     * @return void
     */
    public function testOnKernelExceptionHttpException()
    {
        $exception   = new HttpException(
            JsonResponse::HTTP_I_AM_A_TEAPOT,
            $message = 'Were you thinking I\'m a churn?',
            null,
            ['Exception-Header' => 'Whoopsi mode']
        );

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals($message, $content['message']);
        $this->assertEquals('Whoopsi mode', $response->headers->get('Exception-Header'));
    }

    /**
     * Test that a LogicException is rendered as internal server error.
     *
     * @return void
     */
    public function testOnKernelExceptionLogicException()
    {
        $exception = new \LogicException('logical? Yes it isn\'t!');

        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->setMethods(['getRequest', 'getException'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getException')->willReturn($exception);

        /** @var GetResponseForExceptionEvent $event */

        $listener = new ExceptionListener();
        $listener->onKernelException($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ERROR', $content['status']);
        $this->assertEquals(JsonResponse::$statusTexts[JsonResponse::HTTP_INTERNAL_SERVER_ERROR], $content['message']);
    }
}
