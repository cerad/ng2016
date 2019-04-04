<?php
namespace AppBundle\Listener;


use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class KernelListener implements EventSubscriberInterface,ContainerAwareInterface
{
    /** @var  ContainerInterface */
    private $container;
    private $secureRoutes = false;
    
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST    => [['onRequest']],
            KernelEvents::CONTROLLER => [['onController']],
            KernelEvents::VIEW       => [['onView']],
            KernelEvents::EXCEPTION  => [['onException']],
            KernelEvents::RESPONSE   => [['onResponseP3P']],
        ];
    }
    public function __construct($secureRoutes)
    {
        $this->secureRoutes = $secureRoutes;
    }
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    /* ===================================================
     * Implements _role processing
     * Implements mandatory project_person_register
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) return;

        $container = $this->container;

        // Disable the listener in case of problems
        if (!$this->secureRoutes) {
            return;
        }
        
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $container->get('security.token_storage');

        $token = $tokenStorage->getToken();

        if ($token === null) {
            return; // need this for debug bar profile nonsense
        };
        /** @var AuthorizationCheckerInterface $authChecker */
        $authChecker = $container->get('security.authorization_checker');

        $request = $event->getRequest();
        $role = $request->attributes->get('_role');

        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            if ($role) {
                $event->setResponse($this->redirectToRoute('app_welcome'));
                $event->stopPropagation();
                return;
            }
            return;
        }
        if ($role && !$authChecker->isGranted($role)) { // die('isGranted failed ' . $role);
            $event->setResponse($this->redirectToRoute('app_welcome'));
            $event->stopPropagation();
            return;
        }
        // Make sure register is called at least once
        $user = $token->getUser();
        if ($user['registered'] !== null) {
            return;
        }
        // Allow this one through
        if ($request->attributes->get('_route') === 'project_person_register') {
            return;
        }
        $event->setResponse($this->redirectToRoute('project_person_register'));
        $event->stopPropagation();
        return;
    }
    public function onController(/** @noinspection PhpUnusedParameterInspection */
        FilterControllerEvent $event)
    {
        return;
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
    private function redirectToRoute($route, array $parameters = array(), $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }
    private function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }
    private function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
    /* ==========================================
     * Need my own exception handler since the default one relies on twig
     *
     */
    public function onException(GetResponseForExceptionEvent $event)
    {
        /** @var Kernel $kernel */
        // $kernel = $event->getKernel(); // Mystery, getEnv is undefined here
        $kernel = $this->container->get('kernel');
        $env = $kernel->getEnvironment();
        if ($env !== 'prod') {
            return;
        }

        // Copied from Symfony KernelEventListener
        $exception = $event->getException();
        $this->logException($exception, sprintf('UNCAUGHT PHP Exception %s: "%s" at %s line %s',
            get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()
        ));

        // Just redirect to home, no real need for a fail whale
        $response = $this->redirectToRoute('app_welcome');

        $event->setResponse($response);
    }
    private function logException(Exception $exception, $message)
    {
        $logger = $this->container->get('logger');

        // Copied from Symfony KernelExceptionListener
        if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
            $logger->critical($message, array('exception' => $exception));
        } else {
            $logger->error($message, array('exception' => $exception));
        }
    }
    // Needed for iframes in some browsers
    public function onResponseP3P(FilterResponseEvent $event)
    {
        // P3P Policy
        $event->getResponse()->headers->set('P3P',
            'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
    }
}