<?php
namespace AppBundle\Action\Project\User\Create;

use AppBundle\Action\AbstractView;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserCreateView extends AbstractView
{
    /** @var  UserCreateForm */
    private $userCreateForm ;

    public function __invoke(Request $request)
    {
        $this->userCreateForm = $request->attributes->get('userCreateForm');

        return new Response($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Create a zAYSO Account</legend>
{$this->userCreateForm->render()}
<br/><br />
<!--
<h4>Do you have a Google Account?</h4>
<br/>
<a href="#" class="btn btn-sm btn-default" role="button">
  <span class="glyphicon glyphicon-plus"></span> 
  Sign up with Google
</a>
<br/><br/>
<h4>Do you have a Facebook Account?</h4>
<br/>
<a href="#" class="btn btn-sm btn-default" role="button">
  <span class="glyphicon glyphicon-plus"></span> 
  Sign up with Facebook
</a>
<br/><br/>
-->
<legend>Already have a Zayso account? </legend>
<a href="{$this->generateURL('app_welcome')}" class="btn btn-sm btn-primary" role="button">
  <span class="glyphicon glyphicon-edit"></span> 
  Sign in
</a>
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
}