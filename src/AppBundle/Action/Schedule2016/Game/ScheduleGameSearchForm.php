<?php
namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractForm;

use Symfony\Component\HttpFoundation\Request;

class ScheduleGameSearchForm extends AbstractForm
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
            'projectKey' => $this->filterScalar($data,'projectKey'),
            'programs'   => $this->filterArray ($data,'programs'),
            'genders'    => $this->filterArray ($data,'genders'),
            'ages'       => $this->filterArray ($data,'ages'),
            'dates'      => $this->filterArray ($data,'dates'),
            'sortBy'     => $this->filterScalar($data,'sortBy',true),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $formData = $this->formData;

        $projectKey = $formData['projectKey'];
        $project    = $this->projects[$projectKey];

        $action = $this->generateUrl($this->getCurrentRouteName());

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 760px;" action="{$action}" method="post">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectKey,'projectKey')}
  </div>
  <div class="form-group">
    <label for="sortBy">Sort By</label>
    {$this->renderInputSelect($project['sortBy'],$formData['sortBy'],'sortBy')}
  </div>
  <br/>
  <div class="form-group">
  <table><tr>
    <td>{$this->renderInputSearchCheckbox($project['dates'],   $formData['dates'],   'dates[]',   'Days')    }</td>
    <td>{$this->renderInputSearchCheckbox($project['programs'],$formData['programs'],'programs[]','Programs')}</td>
    <td>{$this->renderInputSearchCheckbox($project['ages'],    $formData['ages'],    'ages[]',    'Ages')    }</td>
    <td>{$this->renderInputSearchCheckbox($project['genders'], $formData['genders'], 'genders[]', 'Genders') }</td>
  </tr></table>
  </div>
  <br/>
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