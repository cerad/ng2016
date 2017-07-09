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

    public function __construct(UserLoginForm $userLoginForm, $showResultsMenu)
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
  If you volunteered at the National Games in 2012, 2014 or 2016, 
  you can simply sign in below and update your plans for the {$this->project['title']}.
  If you need help with your password, <a href="{$this->generateUrl('user_password_reset_request')}">click here</a>.
  Otherwise, <a href="{$this->generateUrl('user_create')}">click here to create a new zAYSO account</a> 
  and start the registration process to referee or volunteer.
</p>
<br/>
<p>
    If you have previously registered on the {$this->project['title']} website, 
    your registration might not have been migrated to zAYSO. If your login fails then just register again here. 
</p>
<br>
    <a href="{$this->generateUrl('user_password_reset_request')}">Click here to reset your zAYSO password</a>.</p>
<br>
    <p>If you need help, contact {$this->project['administrator']['name']} at <a href="mailto:{$this->project['administrator']['email']}">{$this->project['administrator']['email']}</a>.</p><br>
    <p>Thank you for your support.</p>
    <br>
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
            Comments or suggestions?  <a href="mailto:{$this->project['feedback']['email']}?subject={$this->project['feedback']['subject']}" target="_top">Please send us feedback</a>.
        </li>
     </ul>
    </div>
EOT;
    }
}
