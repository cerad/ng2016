<?php
namespace AppBundle\Action\RegTeam\Transform;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamTransformView extends AbstractView2
{
    private $form;
    private $writer;
    
    public function __construct(
        RegTeamTransformForm $form,
        RegTeamTransformWriterExcel $writer
    ) {
        $this->form   = $form;
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $regTeams = $request->attributes->get('regTeams');
        if (!$regTeams) {
            return $this->newResponse($this->renderPage());
        }
        $sheet = $request->attributes->get('sheet');
        
        $writer = $this->writer;

        $response = new Response();
        
        $response->setContent($writer->write($regTeams,$sheet));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName = 'RegTeams2016_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $outFileName);

        return $response;
    }
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->form->render()}
</div>
<hr>
<p>
  Columns J,K,L,M should contain team number, sar, coach first name, coach last name
</p>
EOD;
        return $this->renderBaseTemplate($content);
    }
}