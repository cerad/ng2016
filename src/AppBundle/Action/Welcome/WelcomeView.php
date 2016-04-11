<?php
namespace AppBundle\Action\Welcome;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Project\User\Login\UserLoginForm;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        return new Response($this->render());
    }
    private function render()
    {
        $version = Kernel::VERSION;

        $userContent = $this->renderUser();

        $content = <<<EOT
  <div id="container">
    <div id="welcome">
      <h1><span>Welcome to</span> NG2016 {$version}</h1>
    </div>
    <div id="status">
      <p>
        Your application is now ready. You can start working on it at:
      </p>
    </div>
    <div>
      {$userContent}
    </div>
  </div>
EOT;
        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
    private function renderUser()
    {
        $user = $this->getUser();
        if ($user) {
            return <<<EOD
User: {$this->escape($user->getAccountName())}
<a href="{$this->generateUrl('cerad_user_logout')}">Logout</a>
EOD;
        }
        return <<<EOD
{$this->userLoginForm->render()}
EOD;
    }
}