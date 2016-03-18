<?php
namespace AppBundle\Action;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractTemplate implements AbstractTemplateInterface
{
    /** @var  BaseTemplate */
    protected $baseTemplate;

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
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
    abstract public function render();
}