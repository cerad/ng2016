<?php
namespace AppBundle\Listener;



use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KernelListener implements EventSubscriberInterface
{
    /** @var  ContainerInterface */
    private $container;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['onView'],
            ],
        ];
    }
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /* =================================================================
     * Creates and renders a view
     */
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        $viewAttrName = '_view';
        if ($request->attributes->has('_format'))
        {
            $viewAttrName .= '_' . $request->attributes->get('_format');
        }
        if (!$request->attributes->has($viewAttrName)) return;

        $viewServiceId = $request->attributes->get($viewAttrName);

        /** @var Callable $view */
        $view = $this->container->get($viewServiceId);

        $response = $view($request);

        $event->setResponse($response);
    }
}