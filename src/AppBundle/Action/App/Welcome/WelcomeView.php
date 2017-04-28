<?php
namespace AppBundle\Action\App\Welcome;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\User\Login\UserLoginForm;

use Symfony\Component\HttpFoundation\Request;

class WelcomeView extends AbstractView2
{
    /** @var  UserLoginForm */
    private $userLoginForm;
    
    /** @var  %show_results_menu% */
    private $showResultsMenu;
    
    private $project;
    
    public function __construct(UserLoginForm $userLoginForm,$showResultsMenu)
    {
        $this->userLoginForm = $userLoginForm;
        $this->showResultsMenu = $showResultsMenu;
    }
    public function __invoke(Request $request)
    {
        $this->project = $this->getCurrentProjectInfo();

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOT
  <div id="welcome">
    <legend>Welcome to the {$this->project['title']}</legend>
  </div>
  {$this->renderNotes()}      
  {$this->renderUser()}
  {$this->renderHelp()}      
EOT;
        return $this->renderBaseTemplate($content);
    }
    private function renderNotes()
    {
        $html = <<<EOT
<div id="notes">
EOT;
        if ($this->showResultsMenu) {
            $html .= <<<EOT
<p>
  If you just want to peruse the Schedules and Results, no need to go any further.  
  You do not need to sign-in to access Schedules or Results above.  To volunteer, you will need to <a href="{$this->generateUrl('user_create')}">create a zAYSO account</a>.
  In either case, you should
<a href="https://www.rainedout.net/team_page.php?a=0588afab19ee214eca29" target="_blank">subscribe to {$this->project['title']} text alerts on RainedOut</a>. 
</p>
<br/>
EOT;
}
        $html .= <<<EOT
<p>
  If you officiated at the National Games in 2012, 2014 or 2016, 
  you can simply sign in below and update your plans for the {$this->project['title']}.
  If you need help remembering your password, 
  you can request help by <a href="{$this->generateUrl('user_password_reset_request')}">clicking here</a>.
</p>
<br/>
<p>
  If this is your first time to a National event, 
  <a href="{$this->generateUrl('user_create')}">click here to create a new zAYSO account</a> 
  and start the registration process to referee or volunteer.
</p>
<br/>
<p>
    If you have previously registered on WooFoo, your registration has been migrated to zAYSO.  <a href="{$this->generateUrl('user_password_reset_request')}">Click here to reset your zAYSO password</a>.
    If you still need help, contact {$this->project['administrator']['name']} at <a href="mailto:{$this->project['administrator']['email']}">{$this->project['administrator']['email']}</a>.
</div>
EOT;

        return $html;
    
    }
    private function renderUser()
    {
        return <<<EOD
<legend>Sign In to Your zAYSO Account</legend>
{$this->userLoginForm->render()}
EOD;
    }
    private function renderHelp()
    {
        return <<<EOT
    <div class="app_help">
    <legend>Need Help?</legend>
    <ul class="cerad-common-help">
        <li>
            Forgot your zAYSO account password?  <a href="{$this->generateUrl('user_password_reset_request')}">Click here to recover your zAYSO password.</a>
        </li>
      <li>
            Need to create an account? <a href="{$this->generateUrl('user_create')}">Click here to create a new zAYSO account</a> .
      </li>
        <li>
            Once you create an account, you will be able to modify your information and availability.
        </li>
        <li>
            If you have comments or suggestions, please submit them by <a href="mailto:{$this->project['feedback']['email']}?subject={$this->project['feedback']['subject']}" target="_top">clicking here</a>.  Thank you for your support.
        </li>
     </ul>
    </div>
EOT;
    }
}
