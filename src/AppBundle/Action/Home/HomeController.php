<?php

namespace AppBundle\Action\Home;

use AppBundle\Action\BaseTemplate;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        return new Response($this->renderPage());
    }
    protected function renderUser()
    {
        $user = $this->getUser();

        return <<<EOD
User: {$this->escape($user->getAccountName())}
<a href="{$this->generateUrl('cerad_user_logout')}">Logout</a>
EOD;
    }
    protected function renderPage()
    {
        $userContent = $this->renderUser();

        $content = <<<EOT
<div id="wrapper">
  <div id="container">
    <div id="home">
      <h1><span>Home Page</span></h1>
    </div>
    <div id="status">
      <p>
        Your application is now ready.
      </p>
    </div>
    <div>
      {$userContent}
    </div>
  </div>
</div>
EOT;
        /** @var  BaseTemplate */
        $baseTemplate = $this->get('app_base_template');

        return $baseTemplate->render($content);
    }
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
}
