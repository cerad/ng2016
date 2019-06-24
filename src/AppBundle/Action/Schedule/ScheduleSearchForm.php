<?php
namespace AppBundle\Action\Schedule;

use AppBundle\Action\AbstractForm;

use Symfony\Component\HttpFoundation\Request;

class ScheduleSearchForm extends AbstractForm
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
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $filter = isset($formData['filter']) ? $formData['filter'] : null;

        $projectId = $formData['projectId'];
        $project   = $this->projects[$projectId];

        $currentRouteName = $this->getCurrentRouteName();
        $action = $this->generateUrl($currentRouteName);
//        $txtUrl = $this->generateUrl($currentRouteName,['_format' => 'txt']);
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
    <td>{$this->renderInputSearchCheckbox($project['ages'],    $formData['ages'],    'ages[]',    'Ages')    }</td>
    <td>{$this->renderInputSearchCheckbox($project['genders'], $formData['genders'], 'genders[]', 'Genders') }</td>
  </tr>
  </table>
  <br/>
  <div class="schedule-search col-xs-8 col-xs-offset-2 clearfix">
       <label class="col-xs-offset-2" for="filter">Filter</label>
      <input type="text" name="filter" id="filter" class="form-control" size="15"
        value="{$filter}" placeholder="Filter Games" />
<div class="form-group">
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span>
    <span>Search</span>
  </button>
    <a href="{$xlsUrl}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Excel</a>
  </div>
</div>
</form>

EOD;
        return $html;
    }
}
