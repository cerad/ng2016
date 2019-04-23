<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Listing;

use Symfony\Component\HttpFoundation\Request;

use Zayso\Common\Traits\FormTrait;
use Zayso\Project\ProjectInterface;

class AdminListingSearchForm
{
    use FormTrait;

    private $projectChoices = [];
    
    public function __construct()
    {
    }
    public function handleRequest(Request $request, ProjectInterface $project, string $sessionSearchKey) : void
    {
        $this->projectChoices[$project->projectId] = $project->abbv;

        if (!$request->isMethod('POST')) return;
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        // TODO This should never fail and should be removed
        if(!isset($data['projectKey'])) {
            $session = $request->getSession();
            if ($session->has($sessionSearchKey)) {
                $data = array_merge($data,$session->get($sessionSearchKey));
            } else {
                $data = [
                    'projectKey'    => $project->projectId,
                    'displayKey'    => 'Plans',
                    'reportKey'     =>  null,
                    'name'          =>  null,
                ];
            }
        }
        // TODO Use FormTrait routines
        $projectKey = filter_var(trim($data['projectKey']), FILTER_SANITIZE_STRING);
        $displayKey = filter_var(trim($data['displayKey']), FILTER_SANITIZE_STRING);
        $reportKey  = filter_var(trim($data['reportKey']),  FILTER_SANITIZE_STRING);
        $name       = filter_var(trim($data['name']),       FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        
        $this->formData = array_merge($this->formData,[
            'projectKey'    => $projectKey,
            'displayKey'    => $displayKey,
            'reportKey'     => $reportKey,
            'name'          => $name,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {

        $projectKey = $this->formData['projectKey'];
        
        $displayKey = $this->formData['displayKey'];
        $displayChoices = [
            'Plans'        => 'Plans',
            'Availability' => 'Avail',
            'User'         => 'User',
        ];
        
        $reportKey = $this->formData['reportKey'];
        $reportChoices = [
            'All'           =>  'All',
            'Referees'      =>  'Referees',
            'Volunteers'    =>  'Volunteers',
            //'Unverified'    =>  'Unverified',
            'Unapproved'    =>  'Unapproved',
            'RefIssues'     =>  'Referees with Issues',
            'VolIssues'     =>  'Volunteers with Issues',
            'FL'            =>  'FL Residents'
        ];
        
        $name = $this->formData['name'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<!--suppress ALL -->
<form role="form" class="form-inline" action="{$this->generateUrl('reg_person_admin_listing')}" method="post">
<div class="col-xs-12">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectKey,'projectKey','projectKey')}
  </div>
  <div class="form-group">
    <label for="displayKey">Display</label>
    {$this->renderInputSelect($displayChoices,$displayKey,'displayKey','displayKey')}
  </div>
  <div class="form-group">
    <label for="reportKey">Report</label>
    {$this->renderInputSelect($reportChoices,$reportKey,'reportKey','reportKey')}
  </div>
  <div class="form-group">
    <label for="name">Name</label>
    <input 
      type="text" id="name" class="form-control"
      name="name" value="{$name}" placeholder="Filter By Name" />
  </div>
  </div>
<div class="col-xs-12 clearfix">
  <div class="form-group pull-right">
    <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
    <button type="submit" class="btn btn-sm btn-primary submit">
      <span class="glyphicon glyphicon-search"></span> 
      <span>Search</span>
    </button>
    <a href="{$this->generateUrl('reg_person_admin_listing',['_format' => 'xls'])}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share"></span> Export to Excel</a> 
    </div>
</div>
</form>

EOD;
        return $html;
    }
}
