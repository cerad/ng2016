<?php
namespace AppBundle\Action\Results\FinalStandings;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class ResultsFinalSearchForm extends AbstractForm
{
    private $conn;
    private $projects;
    private $projectChoices;

    public function __construct(
        $projectChoices,
        $projects, Connection $conn
    )
    {
        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
        $this->conn           = $conn;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;

        $data = $request->request->all();
        $errors = [];

        $this->formData = array_replace($this->formData,[
            'projectId'   => $this->filterScalarString($data,'projectId'),
            'program'     => $this->filterScalarString($data,'program'),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $program   = $this->formData['program'];
        $projectId = $this->formData['projectId'];
        $project   = $this->projects[$projectId];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
EOD;
        if ($this->isGranted('ROLE_ADMIN')) {
            $html .= <<<EOD
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl($this->getCurrentRouteName())}" method="post">
  <div class="form-group"  {$this->isAdminStyle()}>
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($project['programs'],$program,'program')}
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span>
    <span>Change Project/Program</span>
  </button>
</form>
<br/>
EOD;
        }
        
        return $html;
    }
}
