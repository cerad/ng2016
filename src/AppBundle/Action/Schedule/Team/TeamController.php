<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamController extends AbstractController
{
    public function __invoke(Request $request)
    {
        return new Response($this->renderPage());
    }
    protected function renderPage()
    {
        $content = <<<EOD
<h1><span>Team Schedule Page</span></h1>
</div>
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
}
