<?php
namespace AppBundle\Action\Results2016\PoolPlay;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class ResultsPoolPlaySearchForm extends AbstractForm
{
    private $conn;
    private $projects;
    private $projectChoices;

    public function __construct(
        $projectChoices,
        $projects, Connection $conn
    )
    {
        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
        $this->conn           = $conn;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        $this->formData = array_replace($this->formData,[
            'projectId'   => $this->filterScalarString($data,'projectId'),
            'program'     => $this->filterScalarString($data,'program'),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $program   = $this->formData['program'];
        $projectId = $this->formData['projectId'];
        $project   = $this->projects[$projectId];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl('results_poolplay_2016')}" method="post">
  <div class="form-group">
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($project['programs'],$program,'program')}
  </div>
  <br/><br />
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Change Project/Program</span>
  </button>
</form>
EOD;
        return $html;
    }
    protected function renderPoolLinks()
    {
        // Query all the relevant pool teams
        $program   = $this->formData['program'];
        $projectId = $this->formData['projectId'];

        $sql = <<<EOD
SELECT poolKey,poolTeamKey,gender,age,division
FROM  poolTeams
WHERE poolTypeKey = 'PP AND projectId = ? and program = ?
ORDER BY gender,age
EOD;
        $projectKey     = $this->project['key'];
        $poolChoices    = $this->project['choices']['pools'];
        $genderChoices  = $this->project['choices']['genders'];
        $programChoices = $this->project['choices']['programs'];

        $html = null;

        // Add Table for each program
        foreach($poolChoices as $programKey => $genders) {

            $programLabel = $programChoices[$programKey];

            $html .= <<<EOD
<table>
  <tr>
    <td class="row-hdr" rowspan="2" style="border: 1px solid black;">{$programLabel}</td>
EOD;
            // Add columns for each gender
            foreach($genders as $genderKey => $ages) {

                $genderLabel = $genderChoices[$genderKey];

                $html .= <<<EOD
    <td class="row-hdr" style="border: 1px solid black;">{$genderLabel}</td>
EOD;
                // Add column for division
                foreach($ages as $age => $poolNames) {
                    $div = $age . $genderKey;
                    $linkParams = [
                        //'div'     => $div,
                        'project'  => $projectKey,
                        'ages'     => $age,
                        'genders'  => $genderKey,
                        'programs' => $programKey
                    ];
                    $html .= <<<EOD
    <td style="border: 1px solid black;">
      <a href="{$this->generateUrl('app_results_poolplay',$linkParams)}">{$div}</a>
EOD;
                    // Add link for each pool
                    foreach($poolNames as $poolName)
                    {
                        $linkParams['pools'] = $poolName;
                        //$linkParams['pool'] = [$poolName,'X','Y','Z'];

                        $html .= <<<EOD
      <a href="{$this->generateUrl('app_results_poolplay',$linkParams)}">{$poolName}</a>
EOD;
                    }
                    // Finish division column
                    $html .= <<<EOD
    </td>
EOD;

                }
                // Force a row shift foreach gender column
                $html .= <<<EOD
    </tr>
EOD;
            }
            // Finish the program table
            $html .= <<<EOD
  </tr>
</table>

EOD;
        }
        return $html;
    }
}