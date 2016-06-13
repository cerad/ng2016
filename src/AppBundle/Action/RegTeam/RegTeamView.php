<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\RegTeam\RegTeamUploadForm;

use Symfony\Component\HttpFoundation\Request;

class RegTeamView extends AbstractView2
{
    /*  @var RegTeamUploadForm */
    private $regTeamUploadForm;
    
    private $isTest;
    
    public function __construct( RegTeamUploadForm $regTeamUploadForm )
    {
        $this->regTeamUploadForm = $regTeamUploadForm;
    }
    public function __invoke(Request $request)
    {
        $this->isTest = $request->attributes->get('isTest');

        return $this->newResponse($this->render());
    }
    protected function render()
    {
        $uploadForm = $this->regTeamUploadForm;
        
        $html = <<<EOD
<legend>Managing Teams: Notes on importing/updating teams</legend>
    <div class="app_help" id="notes">
    <ul class="cerad-common-help ul_bullets">
        <li>Teams can be modified using this page.</li>
        <li><strong>Important: </strong>You must use the same format worksheet as is exported. <strong>NO added columns or rows</strong>.</li>
        <li>The <strong><em>only</em></strong> proper way to do this is to:
            <ol style="list-style: decimal">
                <li><a href="{$this->generateUrl('regteam_2016', ['_format' => 'xls'])}">Export the teams to Excel</a></li>
                <li>In Excel, update the team names, Soccerfest Points or team slots in the pools</li>
                <li>Save the Team workbook</li>
                <li>Using the controls below:
                    <ol>
                        <li>Select your updated workbook by clicking the "Browse" button.</li>
                        <li>Complete the import by clicking the "Upload" button.</li>
                        <li><a href="{$this->generateUrl('results_poolplay_2016')}">View Pool Standings</a> to verify your changes.</li>
                    </ol>
                </li>
            </ol>
        </li>
    </ul>
    </div>
<br>
EOD;
        if (!$this->isTest) {
            $html .= $this->renderSuccessModal();
        }
        
        $html .= $uploadForm->render();
        
        return $this->renderBaseTemplate($html);
    }
    private function renderSuccessModal()
    {
        $html = <<<EOD
<div class="modal fade" id="modalTestSuccess" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">File import test result:  success!</h4>
            </div>
            <div class="modal-body">
                <p>TODO: fix when this is displayed on refresh</p>                     
            </div>    
        </div>
    </div>
</div>
EOD;
        return $html;
    }
}
