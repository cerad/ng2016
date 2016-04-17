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
<h3>Create a Zayso Account</h3>
<br/>
{$this->userCreateForm->render()}
<br/><br />
<!--
<h4>Do you have a Google Account?</h4>
<br/>
<a href="#" class="btn btn-small btn-primary" role="button">
  <span class="glyphicon glyphicon-plus"></span> 
  Sign up with Google
</a>
<br/><br/>
<h4>Do you have a Facebook Account?</h4>
<br/>
<a href="#" class="btn btn-small btn-primary" role="button">
  <span class="glyphicon glyphicon-plus"></span> 
  Sign up with Facebook
</a>
<br/><br/>
-->
<h4>Already have a Zayso account? <a href="{$this->generateUrl('app_welcome')}">Sign In.</a></h4>
EOD;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
}