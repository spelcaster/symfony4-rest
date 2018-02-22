<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Api\ApiProblemException;
use App\Api\ApiProblem;
use App\Api\ResponseFactory;

/**
 * Class ApiExceptionSubscriber
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Flag to check if it's in debug mode
     *
     * @var bool
     */
    protected $isDebug;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * ApiExceptionSubscriber ctor
     *
     * @param bool $isDebug App is in debug mode?
     */
    public function __construct(
        bool $isDebug, ResponseFactory $responseFactory
    ) {
        $this->isDebug = $isDebug;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle kernel exception events
     *
     * @param GetResponseForExceptionEvent $event The event to be handled
     *
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        $e = $event->getException();

        $statusCode = 500;
        $shouldShowDetail = false;
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();

            // there are better ways to do this!!!
            $shouldShowDetail = true;
        }

        if ((500 == $statusCode) && ($this->isDebug)) {
            return;
        }

        if ($e instanceof ApiProblemException) {
            $apiProblem = $e->getApiProblem();
            $event->setResponse(
                $this->responseFactory->createResponse($apiProblem)
            );
            return;
        }

        $apiProblem = new ApiProblem($statusCode);

        if ($shouldShowDetail) {
            $apiProblem->set('detail', $e->getMessage());
        }

        $event->setResponse(
            $this->responseFactory->createResponse($apiProblem)
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }
}
