<?php
namespace AppBundle\Action\Home;

use AppBundle\Action\PageTemplate;

use Cerad\Bundle\UserBundle\Action\Login\LoginForm;

class HomePageTemplate extends PageTemplate
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
        $userContent = $this->renderUser($params['loginForm']);

        $content = <<<EOT
<div id="wrapper">
  <div id="container">
    <div id="home">
      <h1><span>Home Page</span></h1>
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
</div>
EOT;
        return $this->baseTemplate->render($content);
    }
}