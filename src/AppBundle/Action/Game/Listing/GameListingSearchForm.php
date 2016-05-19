<?php
namespace AppBundle\Action\Game\Listing;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class GameListingSearchForm extends AbstractForm
{
    private $conn;
    private $projects;
    private $projectChoices;

    public function __construct(
        $projectChoices,
        $projects, Connection $conn
    ) {
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
            'division'    => $this->filterScalarString($data,'division'),
            'show'        => $this->filterScalarString($data,'show'),
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $show      = $this->formData['show'];
        $program   = $this->formData['program'];
        $projectId = $this->formData['projectId'];
        $division  = $this->formData['division'];

        $project   = $this->projects[$projectId];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl($this->getCurrentRouteName())}" method="post">
  <div class="form-group">
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices,$projectId,'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($project['programs'],$program,'program')}
  </div>
  <div class="form-group">
    <label for="division">Div</label>
    {$this->renderInputSelect($this->divisionChoices,$division,'division')}
  </div>
  <div class="form-group">
    <label for="show">Show</label>
    {$this->renderInputSelect($this->showChoices,$show,'show')}
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Change Project/Program</span>
  </button>
</form>
<br/>
EOD;
        return $html;
    }
    private $showChoices = [
        'all'      => 'All',
        'regTeams' => 'Registered Teams',
        'pools'    => 'Pools',
        'games'    => 'Games',
    ];
    private $divisionChoices = [
        'U10B' => 'U-10 Boys',
        'U10G' => 'U-10 Girls',
        'U12B' => 'U-12 Boys',
        'U12G' => 'U-12 Girls',
        'U14B' => 'U-14 Boys',
        'U14G' => 'U-14 Girls',
        'U16B' => 'U-16 Boys',
        'U16G' => 'U-16 Girls',
        'U19B' => 'U-19 Boys',
        'U19G' => 'U-19 Girls',
    ];
}