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
    <legend>Welcome to the AYSO National Games 2016</legend>
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
  You do not need to sign-in to access Schedules or Results above.
</p>
<p>
<a href="https://www.rainedout.net/team_page.php?a=0588afab19ee214eca29" target="_blank">Join AYSO National Games 2016 text alerts on RainedOut</a> 
</p>
<br/>
EOT;
}
        $html .= <<<EOT
<p>
  To volunteer, you will need to create a zAYSO account.  
  If you officiated at the 2012 National Games in Tennesee or 2014 National Games in Southern California, 
  you can simply sign in below and update your plans for the 2016 National Games.
  If you need help remembering your password, 
  you can request help by <a href="{$this->generateUrl('user_password_reset_request')}">clicking here</a>.
</p>
<br/>
<p>
  If this is your first time to the National Games (you are in for a treat), 
  <a href="{$this->generateUrl('user_create')}">click here to create a new zAYSO account</a> 
  and start the registration process to referee or volunteer.
</p>
<br/>
<p>
    If you have previously registered on Blue Sombrero or WooFoo, your registration has been migrated to zAYSO.  <a href="{$this->generateUrl('user_create')}">Click here to reset your zAYSO password</a>.
    If you still need help, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a>.
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
            If you have comments or suggestions, please submit them by <a href="mailto:feedback.ng2016@gmail.com?subject=Registration %20Feedback" target="_top">clicking here</a>.  Thank you for your support.
        </li>
     </ul>
    </div>
EOT;
    }
}