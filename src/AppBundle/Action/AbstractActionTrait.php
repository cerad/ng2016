<?php
namespace AppBundle\Action;

// Better name
use LogicException;
use Swift_Mailer;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

trait AbstractActionTrait
{
    use ContainerAwareTrait;
    
    protected function getCurrentProject()
    {
        return $this->container->getParameter('app_project');
    }
    protected function getCurrentProjectInfo()
    {
        return $this->container->getParameter('app_project')['info'];
    }
    protected function getCurrentProjectKey()
    {
        return $this->container->getParameter('app_project_key');
    }
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var RouterInterface $router */
        $router = $this->container->get('router');

        return $router->generate($route, $parameters, $referenceType);
    }
    protected function generateUrlAbsoluteUrl($route, $parameters = array())
    {
        /** @var RouterInterface $router */
        $router = $this->container->get('router');

        return $router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
    protected function getCurrentRouteName()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');
        $request = $requestStack->getMasterRequest();
        return $request->attributes->get('_route');
    }
    /** ================================================================
     * TODO This is an infrequently used light weight object, does it really belong here?
     * 
     * @return AuthenticationUtils 
     */
    protected function getAuthenticationUtils() 
    {
        return $this->container->get('security.authentication_utils');
    }
    /** ================================================================
     * This is good because mailer is a heavy object and only want to create when needed
     * 
     * @return Swift_Mailer
     */
    protected function getMailer() 
    {
        return $this->container->get('mailer');
    }
    /** 
     * Pulled from Symfony Framework Base Controller
     * Adjusted style to make PHPStorm happy
     * 
     * TODO: Instead of returning a null when not logged in, should return a guest object
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException('The SecurityBundle is not registered in your application.');
        }
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        
        $token = $tokenStorage->getToken();
        
        if (!$token) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
    // Current registered person's id if logged in, null otherwise
    protected function getUserRegPersonId()
    {
        $user = $this->getUser();
        return $user ? $user->getRegPersonId() : null;
    }
    /** Copied directly from Symfony Framework Base Controller
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes The attributes
     * @param mixed $object     The object
     *
     * @return bool
     *
     * @throws LogicException
     */
    protected function isGranted($attributes, $object = null)
    {
        if (!$this->container->has('security.authorization_checker')) {
            throw new LogicException('The SecurityBundle is not registered in your application.');
        }

        return $this->container->get('security.authorization_checker')->isGranted($attributes, $object);
    }
}