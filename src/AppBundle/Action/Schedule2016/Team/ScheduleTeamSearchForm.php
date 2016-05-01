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
        
        $projectKey = filter_var(trim($data['projectKey']), FILTER_SANITIZE_STRING);
        $program    = filter_var(trim($data['program']),    FILTER_SANITIZE_STRING);
        $name       = filter_var(trim($data['name']),       FILTER_SANITIZE_STRING);

        $dataTeams = isset($data['teams']) ? $data['teams'] : [];
        $teams = [];
        foreach($dataTeams as $team) {
            $team = filter_var(trim($team),FILTER_SANITIZE_STRING);
            if ($team) {
                $teams[] = $team;
            }
        }
        $this->formData = array_merge($this->formData,[
            'projectKey' => $projectKey,
            'program'    => $program,
            'teams'      => $teams,
            'name'       => $name,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $projectKey = $formData['projectKey'];

        $program = $formData['program'];
        $programChoices = $this->projects[$projectKey]['programs'];

        $teamIds = $formData['teams'];
        $criteria = [
            'projectKeys' => [$projectKey],
            'programs'    => [$program],
        ];
        $teams = $this->finder->findProjectTeams($criteria,true);
        $teamChoices = ['Select Team(s)' => null];
        foreach($teams as $team) {
            $teamLabel = sprintf('%s %s',$team->division,$team->name);
            $teamChoices[$teamLabel] = $team->id;
        }

        $name = $this->formData['name'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 760px;" action="{$this->generateUrl('schedule_team_2016')}" method="post">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderFormControlInputSelect($this->projectChoices,$projectKey,'projectKey','projectKey')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderFormControlInputSelect($programChoices,$program,'program','program')}
  </div>
  <div class="form-group">
    <label for="teams">Teams</label>
    {$this->renderFormControlInputSelect($teamChoices,$teamIds,'teams','teams[]',10)}
  </div>
  <div class="form-group">
    <label for="name">Name</label>
    <input 
      type="text" id="name" class="form-control"
      name="name" value="{$name}" placeholder="Filter By Name" />
  </div>
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