<?php
namespace AppBundle\Action\App\Welcome;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Project\User\Login\UserLoginForm;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class WelcomeView extends AbstractView
{
    /** @var  UserLoginForm */
    private $userLoginForm;
    
    public function __construct(UserLoginForm $userLoginForm)
    {
        $this->userLoginForm = $userLoginForm;
    }
    public function __invoke(Request $request)
    {
        $params['base_dir'] =  $request->attributes->get('base_dir');

        return new Response($this->render($params));
    }
    private function render($params = [])
    {
        $version = Kernel::VERSION;

        $content = <<<EOT
    <div class="container">
        {$this->renderDevHeader($params)}
        
      <div id="welcome">
        <legend><span>Welcome to</span> NG2016 {$version}</legend>
      </div>
    
        {$this->renderNotes()}
            
        {$this->renderUser()}

        {$this->renderHelp()}
        
    </div> <!-- class="container" -->

EOT;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
    private function renderDevHeader($params = [])
    {
        return <<<EOT
    <div id="status">
      <p>
        Your application is now ready. You can start working on it at:
        <code>{$params['base_dir']}/</code>
      </p>
    </div>
EOT;

    }
    private function renderNotes()
    {
        return <<<EOT
    <div id="notes">
        <p>If you just want to peruse the Schedules and Results, no need to go any further.  You do not need to sign-in to access Schedules or Results above.</p>
        <br/>
        <p>To volunteer to officiate, you will need to create a ZAYSO account.  If you officiated at the 2012 National Games in Tennesee or 2014 National Games in Southern California, you can simply sign-in below and update your plans for the 2016 National Games.
            If you need help remembering your password, you can request help by <a href="{$this->generateUrl('user_password_reset_request')}">clicking here</a>.</p>
        <br/>
        <p>If this is your first time to the National Games (you are in for a treat), click "Create New Account" in the menu above and get started.</p>
    </div>
    <br/>
EOT;
    }
    private function renderUser()
    {
        $user = $this->getUser();
        if ($user) {
            return <<<EOD
<div>
User: {$this->escape($user->getAccountName())}
<!-- <a href="{$this->generateUrl('cerad_user_logout')}">Logout</a> -->
</div>
EOD;
        }
        return <<<EOD
<legend>Sign In to Your Account</legend>
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
            Forgot your ZAYSO account password?  <a href="{$this->generateUrl('user_password_reset_request')}">Click here to recover your ZAYSO password.</a>
        </li>
      <li>
            Need to create an account? Click "Create New Account" in the menu above to create an account.
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