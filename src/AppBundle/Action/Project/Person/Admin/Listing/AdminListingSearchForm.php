<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractForm;

use Symfony\Component\HttpFoundation\Request;

class AdminListingSearchForm extends AbstractForm
{
    private $projectChoices;
    
    public function __construct(array $projectChoices)
    {
        $this->projectChoices = $projectChoices;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        $displayKey = filter_var(trim($data['displayKey']), FILTER_SANITIZE_STRING);
        $projectKey = filter_var(trim($data['projectKey']), FILTER_SANITIZE_STRING);
        $name       = filter_var(trim($data['name']),       FILTER_SANITIZE_STRING);
        
        $this->formData = array_merge($this->formData,[
            'displayKey' => $displayKey,
            'projectKey' => $projectKey,
            'name'       => $name,
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
        $name = $this->formData['name'];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 760px;" action="{$this->generateUrl('project_person_admin_listing')}" method="post">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectKey,'projectKey','projectKey')}
  </div>
  <div class="form-group">
    <label for="displayKey">Display</label>
    {$this->renderInputSelect($displayChoices,$displayKey,'displayKey','displayKey')}
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