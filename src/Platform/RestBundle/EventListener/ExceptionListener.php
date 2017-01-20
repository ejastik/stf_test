<?php

namespace Platform\RestBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct (ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @return \FOS\RestBundle\View\View|void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (($event->getException()->getCode() == 500) || ($event->getException()->getCode() == 0))
        {
            $error = $this->container->get('error.service')->handleServerError(__FILE__, $event->getException());

            $response = new Response();
            $response->setContent(json_encode($error->getData()));
            $response->setStatusCode($error->getStatusCode());
            $response->headers->replace($error->getHeaders());

            $event->setResponse($response);
        }

        return;
    }
}