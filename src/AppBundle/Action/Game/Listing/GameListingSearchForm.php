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
        $projects,
        Connection $conn
    ) {
        $this->projects = $projects;
        $this->projectChoices = $projectChoices;
        $this->conn = $conn;
    }

    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }

        $this->isPost = true;

        $data = $request->request->all();
        $errors = [];

        $this->formData = array_replace(
            $this->formData,
            [
                'projectId' => $this->filterScalarString($data, 'projectId'),
                'program' => $this->filterScalarString($data, 'program'),
                'division' => $this->filterScalarString($data, 'division'),
                'show' => $this->filterScalarString($data, 'show'),
            ]
        );
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $show = $this->formData['show'];
        $program = $this->formData['program'];
        $projectId = $this->formData['projectId'];
        $division = $this->formData['division'];

//        $project = $this->projects[$projectId];

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 1200px;" action="{$this->generateUrl($this->getCurrentRouteName())}" method="post">
  <div class="form-group">
    <label for="projectId">Project</label>
    {$this->renderInputSelect($this->projectChoices, $projectId, 'projectId')}
  </div>
  <div class="form-group">
    <label for="program">Program</label>
    {$this->renderInputSelect($this->programChoices, $program, 'program')}
  </div>
  <div class="form-group">
    <label for="division">Div</label>
    {$this->renderInputSelect($this->divisionChoices($program), $division, 'division')}
  </div>
  <div class="form-group">
    <label for="show">Show</label>
    {$this->renderInputSelect($this->showChoices, $show, 'show')}
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Search</span>
  </button>
</form>
<br/>
EOD;

        return $html;
    }

    private $showChoices = [
        'all' => 'All',
        'regTeams' => 'Registered Teams',
        'poolTeams' => 'Pool Teams',
        'games' => 'Games',
        'gameNumbers' => 'Game Numbers',
    ];
    private $programChoices = [
        null => 'All',
        'Core' => 'Core'
    ];

    private function divisionChoices($program)
    {
        $program = is_null($program)? 'All': $program;

        $divisionChoices = [
            null => 'All',
        ];
        if ($program == 'All' || $program == 'Core') {
            $divisionChoices = array_merge(
                $divisionChoices,
                [
                    '10UB' => 'Boys 10U',
                    '10UG' => 'Girls 10U',
                    '12UB' => 'Boys 12U',
                    '12UG' => 'Girls 12U',
                    '14UB' => 'Boys 14U',
                    '14UG' => 'Girls 14U',
                    '19UB' => 'Boys 19U',
                    '19UG' => 'Girls 19U',
                ]
            );
        }

        return $divisionChoices;
    }
}