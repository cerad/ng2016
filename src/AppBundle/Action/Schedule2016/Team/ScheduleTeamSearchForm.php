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
            'projectKey' => $this->filterScalar($data,'projectKey'),
            'program'    => $this->filterScalar($data,'program'),
            'name'       => $this->filterScalar($data,'name'),
            'teams'      => $this->filterArray ($data,'teams'),
            'sortBy'     => $this->filterScalar($data,'sortBy',true),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $projectKey = $formData['projectKey'];
        $project    = $this->projects[$projectKey];

        $program = $formData['program'];
        
        $teamIds = $formData['teams'];
        $criteria = [
            'projectKeys' => [$projectKey],
            'programs'    => [$program],
        ];
        // findProjectTeamChoices ???
        $teams = $this->finder->findRegTeams($criteria,true);
        $teamChoices = [null => 'Select Team(s)'];
        foreach($teams as $team) {
            $teamContent = sprintf('%s %s',$team->division,$team->name);
            $teamChoices[$team->id] = $teamContent;
        }

        $name = $this->formData['name'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl('schedule_team_2016')}" method="post">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectKey,'projectKey')}
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
    <label for="name">Name</label>
    <input 
      type="text" id="name" class="form-control"
      name="name" value="{$name}" placeholder="Filter By Name" />
  </div>
  <div class="form-group">
    {$this->renderInputSelect($teamChoices,$teamIds,'teams[]','teams',10)}
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