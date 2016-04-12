<?php
namespace AppBundle\Listener;


use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST    => [['onRequest']],
            KernelEvents::CONTROLLER => [['onController']],
            KernelEvents::VIEW       => [['onView']],
        ];
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
        // TODO Use session info or maybe the ProjectUser to avoid the query
        if ($request->attributes->get('_route') === 'project_person_register') {
            return;
        }
        if (!$container->has('doctrine.dbal.ng2016_connection')) {
            return; // Only for ng2016 stuff
        }
        /** @var Connection $conn */
        $conn       = $container->get('doctrine.dbal.ng2016_connection');
        $projectKey = $container->getParameter('app_project_key');
        $personKey  = $token->getUser()['personKey'];

        $stmt = $conn->prepare('SELECT id FROM project_persons WHERE projectKey = ? AND personKey = ?');
        $stmt->execute([$projectKey, $personKey]);
        if (!$stmt->fetch()) {
            $event->setResponse($this->redirectToRoute('project_person_register'));
            $event->stopPropagation();
            return;
        }
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
}