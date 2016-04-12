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

        $passwordReset = $this->router->generate('user_password_reset_request');
        $userCreate    = $this->router->generate('user_create');
        
        return  <<<EOT
{$this->renderError()}
<form class="cerad_tourn_account_login cerad_common_form1 app_form" action="{$loginCheckPath}" method="post">
    <div class="row col-xs-12">
            <label class="form-label col-xs-2 vcenter" for="username"><span class="pull-right">Email</span></label>
            <input class="form-control col-xs-1" type="text" id="username" name="_username" value="{$lastUsername}" /><br />
    </div>
    <div class="row col-xs-12">
            <label class="form-label col-xs-2 vcenter" for="password"><span class="pull-right">Password</span></label>
            <input class="form-control col-xs-1" type="password" id="password" name="_password" /><br />
    </div>
    <div class="row col-xs-12">
        <label for="remember_me" style="display:none">Remember me</label>
        <input type="checkbox" id="remember_me" name="_remember_me" style="display:none" />
    
        <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
    </div>
    
    <div class="row col-xs-12">
          <button type="submit" class="btn btn-sm btn-primary submit"><span class="glyphicon glyphicon-edit"></span><span  style="padding-left:10px">Sign In</span></button>
    </div>
    
    <div class="row col-xs-12 text-center">
        <a href="{$loginGoogle}">Google</a> |
        <a href="{$loginFacebook}">Facebook</a> |
        <a href="{$passwordReset}">Reset Password</a> |
        <a href="{$userCreate}">Create User</a>
    </div>
</form>

EOT;
    }
}