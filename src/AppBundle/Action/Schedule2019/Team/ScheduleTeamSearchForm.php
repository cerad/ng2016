<?php
namespace AppBundle\Action\Schedule2019\Team;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Schedule2019\ScheduleFinder;
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
            'projectId'   => $this->filterScalar($data,'projectId'),
            'program'     => $this->filterScalar($data,'program'),
            'regTeamName' => $this->filterScalar($data,'regTeamName'),
            'regTeams'    => $this->filterArray ($data,'regTeams'),
            'sortBy'      => $this->filterScalar($data,'sortBy',true),
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

        $regTeamName = $this->formData['regTeamName'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" action="{$this->generateUrl('schedule_team_2016')}" method="post">
<div class="col-xs-12 schedule-search">
  <div class="form-group" {$this->isAdminStyle()}>
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
    <label for="regTeamName">Name</label>
    <input
      type="text" id="regTeamName" class="form-control"
      name="name" value="{$regTeamName}" placeholder="Filter By Name" />
  </div>
  </div>
<div class="col-xs-12 schedule-search">
  <div class="form-group">
    <label for="regTeamx">Select Teams</label>
    {$this->renderInputSelect($regTeamChoices,$regTeamIds,'regTeams[]','regTeams',10)}
  </div>
</div>

  <div class="form-group col-xs-8 col-xs-offset-2 clearfix">

    <a href="{$this->generateUrl('schedule_team_2016',['_format' => 'txt'])}" class="btn btn-sm btn-primary pull-right"><span class="glyphicon glyphicon-share"></span> Export to Text</a>
    <a href="{$this->generateUrl('schedule_team_2016',['_format' => 'xls'])}" class="btn btn-sm btn-primary pull-right"><span class="glyphicon glyphicon-share"></span> Export to Excel</a>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit pull-right">
    <span class="glyphicon glyphicon-search"></span>
    <span>Search</span>
  </button>
</div>
<div class="clearfix"></div>
</form>
EOD;

        return $html;
    }
}
