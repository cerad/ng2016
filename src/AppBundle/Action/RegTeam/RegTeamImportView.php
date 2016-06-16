<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\RegTeam\RegTeamUploadForm;

use Symfony\Component\HttpFoundation\Request;

class RegTeamImportView extends AbstractView2
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
                <li><a href="{$this->generateUrl('regteam_export')}">Export the teams to Excel</a></li>
                <li>In Excel, update the team names, Soccerfest Points or team slots in the pools</li>
                <li>Save the Team workbook</li>
                <li>Using the controls below:
                    <ol>
                        <li>Select your updated workbook by clicking the "Browse" button.</li>
                        <li>Test the import by clicking the "Test Upload" button.</li>
                        <li>If there are errors, return to Step 2 and fix updated workbook.</li>
                        <li>Select your updated workbook by clicking the "Browse" button.</li>
                        <li>Complete the import by clicking the "Upload" button.</li>
                        <li><a href="{$this->generateUrl('game_listing')}">View Game Listing</a> to verify your changes.</li>
                    </ol>
                </li>
            </ol>
        </li>
    </ul>
    </div>
<br>
EOD;
        
        $html .= $uploadForm->render();
        
        return $this->renderBaseTemplate($html);
    }
}
