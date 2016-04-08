<?php
namespace AppBundle\Action;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractView
{
    /** @var  BaseTemplate */
    protected $baseTemplate;

    protected $project;

    /** @var  TokenStorageInterface */
    private $securityTokenStorage;

    /** @var  AuthorizationCheckerInterface */
    private $securityAuthorizationChecker;

    /** @var  RouterInterface */
    private $router;

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }
    public function setSecurity(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->securityTokenStorage = $tokenStorage;
        $this->securityAuthorizationChecker = $authorizationChecker;
    }
    public function setBaseTemplate(BaseTemplate $baseTemplate)
    {
        $this->baseTemplate = $baseTemplate;
    }
    public function setProject(array $project)
    {
        $this->project = $project['info'];
    }
    protected function getUser()
    {
        $token = $this->securityTokenStorage->getToken();

        if (!$token) return null;

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }
        return $user;
    }
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }
    protected function isGranted($attributes, $object = null)
    {
        return $this->securityAuthorizationChecker->isGranted($attributes, $object);
    }
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
}