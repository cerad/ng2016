<?php
namespace AppBundle\Action\Project\User\Login;

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
        $loginCheckPath = $this->router->generate('user_login_check');

        return  <<<EOT
{$this->renderError()}
<form action="{$loginCheckPath}" method="post">
<div class="col-xs-3">
<div class="row">
    <label for="username">Username:</label>
    <input type="text" id="username" name="_username" value="{$lastUsername}" /><br />
</div>
<div class="row">

    <label for="password">Password:</label>
    <input type="password" id="password" name="_password" /><br />
</div>
<div class="row">
    <label for="remember_me" style="display:none">Remember Me:</label>
    <input type="checkbox" id="remember_me" name="_remember_me" style="display:none" />

    <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
</div>

 <div class="row">
      <button type="submit" class="float-right btn btn-sm btn-primary submit"><span class="glyphicon glyphicon-edit"></span>Sign In</button>
</div>
</div>
</form>
EOT;
    }
}