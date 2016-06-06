<?php
namespace AppBundle\Action\RegPerson\Teams\Update;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\RegPerson\RegPersonFinder;
use AppBundle\Action\RegPerson\RegPersonTeam;
use Symfony\Component\HttpFoundation\Request;

class TeamsUpdateForm extends AbstractForm
{
    /** @var  RegPersonFinder */
    private $regPersonFinder;

    /** @var  RegPersonTeam[] */
    private $regPersonTeams = [];

    private $addTeamId;
    private $removeTeamIds = [];
    
    public function __construct(
        RegPersonFinder $regPersonFinder
    ) {
        $this->regPersonFinder = $regPersonFinder;
    }
    public function setRegPersonTeams(array $regPersonTeams)
    {
        $this->regPersonTeams = $regPersonTeams;
    }
    public function getRegPersonTeamAdd() {
        return $this->addTeamId;
    }
    public function getRegPersonTeamsRemove() {
        return $this->removeTeamIds;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();

        $this->addTeamId =  $this->filterScalarString($data, 'addTeamId');
        
        $removeIds = $this->filterArray($data,'removeIds');
        if ($removeIds) {
            foreach($removeIds as $removeId) {
                if ($removeId) {
                    $this->removeTeamIds[] = $removeId;
                }
            }
        }
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $addChoices = [
            'add' => 'Add',
        ];
        $teamChoices = array_merge(
            [null => 'Add Team'],
            $this->regPersonFinder->findRegTeamChoices($this->getUser()->getProjectId())
        );

        $html = <<<EOD
<table style="min-width: 500px;">
  <tr><th colspan="3" style="text-align: center;">My Teams</th></tr>
  <tr><th>Team Name</th><th>Add/Remove</th></tr>
<form method="post" action="{$this->generateUrl('reg_person_teams_update')}" class="form-inline role="form"">
EOD;
        foreach($this->regPersonTeams as $regPersonTeam) {
            $html .= $this->renderRegPersonTeam($regPersonTeam);
        }
        $html .= <<<EOD
  <tr>
    <td>{$this->renderInputSelect($teamChoices,null,'addTeamId','addTeamId')}</td>
    <td>{$this->renderInputSelect($addChoices,'add','addChoice','addChoice')}</td>
  </tr>
  <tr>
    <td><a href="{$this->generateUrl('app_home')}">Back to Home</a></td>
    <td><button type="submit" name="submit" value="submit" class="btn btn-default">Add/Remove Teams(s)</button></td>
  </tr>
</form>
</table>
<br/>
EOD;
        return $html;
    }
    private function renderRegPersonTeam(RegPersonTeam $regPersonTeam)
    {
        $role = $regPersonTeam->role;
        $name = $this->escape(($regPersonTeam->teamName));
        
        $teamId = $regPersonTeam->teamId;
        $removeId = $role . ' ' . $teamId;
        $removeChoices = [
             null     => 'Keep',
            $removeId => 'Remove',
        ];
        $remove = $this->renderInputSelect($removeChoices,null,'removeIds[]','removeIds');
        
        return <<<EOD
  <tr><td>{$name}</td><td>{$remove}</td></tr>
EOD;

    }
}
