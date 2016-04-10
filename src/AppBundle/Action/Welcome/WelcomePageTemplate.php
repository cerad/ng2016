<?php
namespace AppBundle\Action\Welcome;

use AppBundle\Action\PageTemplate;

use Symfony\Component\HttpKernel\Kernel;

use AppBundle\Action\Project\User\Login\LoginForm;

class WelcomePageTemplate extends PageTemplate
{
    protected function renderUser(LoginForm $loginForm)
    {
        $user = $this->getUser();
        if ($user) {
            return <<<EOD
User: {$this->escape($user->getAccountName())}
<a href="{$this->generateUrl('cerad_user_logout')}">Logout</a>
EOD;
        }
        return <<<EOD
{$loginForm->render()}
EOD;
    }
    public function render($params = [])
    {
        $version = Kernel::VERSION;

        $userContent = $this->renderUser($params['loginForm']);

        $content = <<<EOT
  <div id="container">
    <div id="welcome">
      <h1><span>Welcome to</span> NG2016 {$version}</h1>
    </div>
    <div id="status">
      <p>
        Your application is now ready. You can start working on it at:
        <code>{$params['base_dir']}/</code>
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
}