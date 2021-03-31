<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\EventListener;

use Exception;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Transforms an exception to a JSON response for OpenAPI routes.
 */
class JsonResponseExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilder;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelExceptionTransformToJsonResponse', 0],
            ],
        ];
    }

    /**
     * Constructs a new JsonResponseExceptionSubscriber instance.
     */
    public function __construct(ExceptionJsonResponseBuilderInterface $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Converts the exception to a JSON response.
     *
     * @param GetResponseForExceptionEvent|ExceptionEvent $event
     */
    public function onKernelExceptionTransformToJsonResponse($event): void
    {
        $routeOptions = $event->getRequest()->attributes->get('_nijens_openapi');

        if (isset($routeOptions['openapi_resource']) === false) {
            return;
        }

        $exception = null;
        if ($event instanceof GetResponseForExceptionEvent) {
            $exception = $event->getException();
        }

        if ($event instanceof ExceptionEvent) {
            $exception = $event->getThrowable();
        }

        if ($exception === null) {
            return;
        }

        if ($exception instanceof Throwable) {
            $exception = new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $event->setResponse($this->responseBuilder->build($exception));
    }
}
