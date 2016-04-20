<?php
namespace AppBundle\Action\Project\User\Login;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class UserLoginForm
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

        $loginGoogle   = $this->router->generate('user_authen_connect',['providerName' => 'google']);
        $loginFacebook = $this->router->generate('user_authen_connect',['providerName' => 'facebook']);
        
        return  <<<EOT
{$this->renderError()}
<form role="form" action="{$loginCheckPath}" method="post">
  <div class="form-group">
    <label for="user_login_username">Email</label>
    <input 
      type="text" id="user_login_username" class="form-control" required
      name="username" value="{$lastUsername}" required placeholder="Zayso Email" />
  </div>
  <div class="form-group">
    <label for="user_login_password" ">
      Password
      <a href="{$this->router->generate('user_password_reset_request')}">&nbsp;&nbsp;&nbsp;&nbsp;Forgot Zayso Password?</a>
    </label>
    <input 
      type="password" id="user_login_password" class="form-control" required
      name="password" value="" required placeholder="********" />
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-edit"></span> Sign In
  </button><br>
  <a href="{$loginGoogle}" class="btn btn-sm btn-default" role="button">
    <!--<span class="glyphicon glyphicon-edit"></span>-->
    Sign In (Google)
  </a>
  <a href="{$loginFacebook}" class="btn btn-sm btn-default" role="button">
    <!--<span class="glyphicon glyphicon-edit"></span>-->
    Sign In (Facebook)
  </a>
</form>
EOT;
    }
}