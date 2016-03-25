<?php
namespace Cerad\Bundle\UserBundle\Action\Login;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class LoginForm
{
    private $authUtils;
    private $csrfTokenManager;
    private $router;

    public function __construct(
        AuthenticationUtils $authUtils,
        CsrfTokenManagerInterface $csrfTokenManager,
        RouterInterface $router
    )
    {
        $this->authUtils = $authUtils;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->router = $router;
    }

    public function renderError()
    {
        $error = $this->authUtils->getLastAuthenticationError();

        if (!$error) return null;

        return <<<EOT
<div>{$error->getMessage()}</div>
EOT;
    }
    public function render()
    {
        $lastUsername = $this->authUtils->getLastUsername();
        $csrfToken = $this->csrfTokenManager->getToken('authenticate');
        $loginCheckPath = $this->router->generate('cerad_user_login_check');

        return  <<<EOT
{$this->renderError()}
<form action="{$loginCheckPath}" method="post">
    <label for="username">Username:</label>
    <input type="text" id="username" name="_username" value="{$lastUsername}" /><br />

    <label for="password">Password:</label>
    <input type="password" id="password" name="_password" /><br />

    <label for="remember_me" style="display:none">Remember Me:</label>
    <input type="checkbox" id="remember_me" name="_remember_me" style="display:none" />

    <input type="hidden" name="_csrf_token" value="{$csrfToken}" />

    <button type="submit">Sign In</button>
</form>
EOT;
    }
}