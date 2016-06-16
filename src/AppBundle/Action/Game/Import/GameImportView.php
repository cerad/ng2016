<?php
namespace AppBundle\Action\Game\Import;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class GameImportView extends AbstractView2
{
    private $form;
    
    public function __construct(GameImportForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
        return $this->newResponse($this->renderPage());
    }
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->form->render()}
</div>
<hr>
EOD;
        return $this->renderBaseTemplate($content);
    }
}