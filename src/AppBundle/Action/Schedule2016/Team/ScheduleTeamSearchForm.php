<?php
namespace AppBundle\Action\Schedule2016\Team;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Schedule2016\ScheduleFinder;
use Symfony\Component\HttpFoundation\Request;

class ScheduleTeamSearchForm extends AbstractForm
{
    private $finder;
    private $projects;
    private $projectChoices;

    public function __construct($projectChoices,$projects, ScheduleFinder $finder)
    {
        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
        $this->finder         = $finder;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        $this->formData = array_replace($this->formData,[
            'projectId' => $this->filterScalar($data,'projectId'),
            'program'   => $this->filterScalar($data,'program'),
            'teamName'  => $this->filterScalar($data,'teamName'),
            'regTeams'  => $this->filterArray ($data,'regTeams'),
            'sortBy'    => $this->filterScalar($data,'sortBy',true),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $projectId = $formData['projectId'];
        $project    = $this->projects[$projectId];

        $program = $formData['program'];
        
        $regTeamIds = $formData['regTeams'];
        $criteria = [
            'projectIds' => [$projectId],
            'programs'   => [$program],
        ];
        // findProjectTeamChoices ???
        $regTeams = $this->finder->findRegTeams($criteria,true);
        $regTeamChoices = [null => 'Select Team(s)'];
        foreach($regTeams as $regTeam) {
            $regTeamContent = sprintf('%s %s',$regTeam->division,$regTeam->teamName);
            $regTeamChoices[$regTeam->regTeamId] = $regTeamContent;
        }

        $teamName = $this->formData['teamName'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl('schedule_team_2016')}" method="post">
  <div class="form-group">
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($project['programs'],$program,'program')}
  </div>
  <div class="form-group">
    <label for="sortBy">Sort By</label>
    {$this->renderInputSelect($project['sortBy'],$formData['sortBy'],'sortBy')}
  </div>
  <div class="form-group">
    <label for="teamName">Name</label>
    <input 
      type="text" id="teamName" class="form-control"
      name="name" value="{$teamName}" placeholder="Filter By Name" />
  </div>
  <div class="form-group">
    {$this->renderInputSelect($regTeamChoices,$regTeamIds,'regTeams[]','regTeams',10)}
  </div>
  <br/><br />
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Search</span>
  </button>
</form>
EOD;
        return $html;
    }
}