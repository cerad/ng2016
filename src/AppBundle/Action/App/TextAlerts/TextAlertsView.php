<?php
namespace AppBundle\Action\App\TextAlerts;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\User\Login\UserLoginForm;

use Symfony\Component\HttpFoundation\Request;

class TextAlertsView extends AbstractView2
{
    public function __construct()
    {
    }
    public function __invoke(Request $request)
    {
        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOT
    <legend><a href="https://www.rainedout.net/team_page.php?a=0588afab19ee214eca29" target="_blank"><img src="http://www.rainedout.com/sites/default/files/RO_badge_grey.png" border="0" alt="RainedOut" class="rainedout-logo-show"></a>AYSO National Games 2016 Alert System</legend>
  <div class="textalerts">
    <p><strong>Create your RainedOut account by following the instructions shown below.</strong></p>
    <br>
    <ul class="cerad-common-help ul_bullets">
        <li><a href="https://www.rainedout.net/team_page.php?a=0588afab19ee214eca29" target="_blank">Subscribe to AYSO National Games 2016 text alerts on RainedOut</a></li>
        <li>You will be able to subscribe and unsubscribe by logging in with your phone number or email address</li>
        <li>For customer support contact <a href="mailto:info@rainedout.com">info@rainedout.com</a> or 800-230-1933</li>
        <li>Messages and data rates may apply</li>
    </ul>
</div>    
    <legend>Latest Announcements</legend>
    <script type="text/javascript" src="http://widgets.omnilert.net/0588afab19ee214eca29"></script>

EOT;
        return $this->renderBaseTemplate($content);
    }
}
