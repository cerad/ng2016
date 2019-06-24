<?php
namespace AppBundle\Action\Results\MedalRound;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class ResultsMedalRoundSearchForm extends AbstractForm
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
EOD;
        if ($this->isGranted('ROLE_ADMIN')) {
            $html .= <<<EOD
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl($this->getCurrentRouteName())}" method="post">
  <div class="form-group"  {$this->isAdminStyle()}>
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($project['programs'],$program,'program')}
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Change Project/Program</span>
  </button>
</form>
<br/>
EOD;
        }
        
        $html .= <<<EOD
{$this->renderPoolLinks()}
EOD;
        return $html;
    }
    private function renderPoolLinks()
    {
        // Query all the relevant pool teams
        $program = 'Core';
        $projectId = $this->formData['projectId'];

        $sql = <<<EOD
SELECT poolKey,poolSlotView,poolTeamKey,program,gender,age,division
FROM  poolTeams
WHERE poolTypeKey = 'PP' AND projectId = ? and program = ?
ORDER BY program,gender,age
EOD;
        $stmt = $this->conn->executeQuery($sql, [$projectId, $program]);
        $pools = [];
        while ($pool = $stmt->fetch()) {
            $pools[$pool['program']][$pool['gender']][$pool['age']][$pool['poolKey']] = $pool;
        }
        $routeName = $this->getCurrentRouteName();
        $html = null;
        
        // Keep the idea of multiple programs for now
        foreach ($pools as $program => $genders) {
            $html .= <<<EOD
<table>
  <tr>
    <td class="row-hdr" rowspan="2" style="border: 1px solid black;">{$program}</td>
EOD;
            foreach ($genders as $gender => $ages) {
                // Should be a transformer of some sort
                $genderLabel = $gender === 'B' ? 'Boys' : 'Girls';
                $html .= <<<EOD
    <td class="row-hdr" style="border: 1px solid black;">{$genderLabel}</td>
EOD;
                foreach ($ages as $age => $poolsForAge) {

                    $division = array_values($poolsForAge)[0]['division'];
                    $linkParams = [
                        //'projectId' => $projectId,
                        //'program'   => $program,  // Have no id for program/division
                        'division'  => $division,
                    ];
                    $html .= <<<EOD
    <td style="border: 1px solid black;">
      <a href="{$this->generateUrl($routeName,$linkParams)}">{$gender}{$age}</a>
EOD;
/*
                    foreach ($poolsForAge as $poolKey => $pool) {
                        $linkParams = [
                            //'projectId' => $projectId,
                            'poolKey'   => $poolKey, // This is unique within a project
                        ];
                        // Need a short pool name view
                        //$poolName = $pool['poolKey'];
                        //$poolName = substr($poolName,strlen($poolName)-1);

                        $html .= <<<EOD
      <a href="{$this->generateUrl($routeName,$linkParams)}">{$pool['poolSlotView']}</a>
EOD;
                    }*/
                    // Finish division column
                    $html .= sprintf("    </td>\n");
                }
                // Force a row shift foreach gender column
                $html .= sprintf("</tr>\n");
            }
            // Finish the program table
            $html .= sprintf("</tr></table>\n");
        }
        return $html;
    }
}
