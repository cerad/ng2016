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
  <div id="textalerts">
    <legend>Welcome to the AYSO National Games 2016</legend>
  </div>
  <a href="https://www.rainedout.net/team_page.php?a=0588afab19ee214eca29" target="_blank">Join AYSO National Games 2016 text alerts on RainedOut</a>  
  <script type="text/javascript" src="https://www.rainedout.net/invite_smartcode.php?a=0588afab19ee214eca29"></script>
  <script type="text/javascript" src="http://widgets.omnilert.net/0588afab19ee214eca29-11380"></script>
EOT;
        return $this->renderBaseTemplate($content);
    }
}