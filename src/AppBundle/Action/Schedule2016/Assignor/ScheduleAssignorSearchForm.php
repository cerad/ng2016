<?php
namespace AppBundle\Action\Schedule2016\Assignor;

use AppBundle\Action\Schedule2016\ScheduleSearchForm;

use Symfony\Component\HttpFoundation\Request;

class ScheduleAssignorSearchForm extends ScheduleSearchForm
{
    private $projects;
    private $projectChoices;

    public function __construct($projectChoices,$projects)
    {
        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;

        $data = $request->request->all();
        $errors = [];

        $this->formData = array_replace($this->formData,[
                    'projectId' => $this->filterScalar($data,'projectId'),
                    'programs'  => $this->filterArray ($data,'programs'),
                    'genders'   => $this->filterArray ($data,'genders'),
                    'ages'      => $this->filterArray ($data,'ages'),
                    'dates'     => $this->filterArray ($data,'dates'),
                    'sortBy'    => $this->filterScalar($data,'sortBy',true),
                    'filter'    => $this->filterScalar($data,'filter'),
                    'reportKey' => $this->filterScalar($data,'reportKey'),
                ]);
        
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $filter = isset($formData['filter']) ? $formData['filter'] : null;

        $projectId = $formData['projectId'];
        $project   = $this->projects[$projectId];
        
        $reportKey = $this->formData['reportKey'];
        $reportChoices = [
            'All'           =>  'All Games',
            'Issues'        =>  'Games with Issues',
            'Open'          =>  'Games with Open Slots',
            'Pending'       =>  'Games with Pending Slots',
            'Published'     =>  'Games with Published Slots',
            'Requested'     =>  'Games with Requested Slots',
            'Turnback'      =>  'Games with Turnback Slots'
        ];        

        $currentRouteName = $this->getCurrentRouteName();
        $action = $this->generateUrl($currentRouteName);
        $txtUrl = $this->generateUrl($currentRouteName,['_format' => 'txt']);
        $xlsUrl = $this->generateUrl($currentRouteName,['_format' => 'xls']);
            
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" action="{$action}" method="post">
<div class="schedule-search">
  <div class="form-group" {$this->isAdminStyle()}>
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="sortBy">Sort By</label>
    {$this->renderInputSelect($project['sortBy'],$formData['sortBy'],'sortBy')}
  </div>
  </div>
  <div class="form-group xs-col-12 schedule-search">
  <table><tr>
    <td>{$this->renderInputSearchCheckbox($project['dates'],   $formData['dates'],   'dates[]',   'Days')    }</td>
    <td>{$this->renderInputSearchCheckbox($project['programs'],$formData['programs'],'programs[]','Programs')}</td>
    <td>{$this->renderInputSearchCheckbox($project['ages'],    $formData['ages'],    'ages[]',    'Ages')    }</td>
    <td>{$this->renderInputSearchCheckbox($project['genders'], $formData['genders'], 'genders[]', 'Genders') }</td>
  </tr>
  </table>
  <br/>

<div class="form-group col-xs-12 clearfix">  
       <label class="col-xs-offset-2" for="filter">Filter</label>
      <input type="text" name="filter" id="filter" class="form-control" size="15"
        value="{$filter}" placeholder="Filter Games" />
    <label class="label-control col-xs-offset-1" for="reportKey">Reports</label>
    {$this->renderInputSelect($reportChoices,$reportKey,'reportKey','reportKey')}
</div>
<br/>

<div class="form-group col-xs-10 col-xs-offset-6 clearfix">
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span>
    <span>Search</span>
  </button>
    <a href="{$xlsUrl}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Excel</a>
    <a href="{$txtUrl}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Text</a>
  </div>
</div>
</form>

EOD;
        return $html;
    }
}
