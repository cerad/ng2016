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
            'projectKey' => $this->filterString ($data,'projectKey'),
            'programs'   => $this->filterArray  ($data,'programs'),
            'genders'    => $this->filterArray  ($data,'genders'),
            'ages'       => $this->filterArray  ($data,'ages'),
            'dates'      => $this->filterArray  ($data,'dates'),
            'sortBy'     => $this->filterInteger($data,'sortBy'),

        ]);
        $this->formDataErrors = $errors;
    }
    protected function filterString($data,$name)
    {
        $itemData = isset($data[$name]) ? $data[$name] : null;
        return filter_var(trim($itemData), FILTER_SANITIZE_STRING);
    }
    protected function filterInteger($data,$name)
    {
        $itemData = isset($data[$name]) ? $data[$name] : null;
        return (integer)filter_var(trim($itemData), FILTER_SANITIZE_STRING);
    }
    protected function filterArray($data,$name)
    {
        $itemData = isset($data[$name]) ? $data[$name] : [];
        $items = [];
        foreach($itemData as $item) {
            $item = filter_var(trim($item),FILTER_SANITIZE_STRING);
            if ($item) {
                $items[] = $item;
            }
        }
        return $items;
    }
    public function render()
    {
        $formData = $this->formData;

        $projectKey = $formData['projectKey'];

        $ageChoices     = $this->projects[$projectKey]['ages'];
        $dateChoices    = $this->projects[$projectKey]['dates'];
        $genderChoices  = $this->projects[$projectKey]['genders'];
        $programChoices = $this->projects[$projectKey]['programs'];

        $sortByChoices = [
            'Date, Time, Pool, Field' => 1,
            'Date, Field, Time'       => 2,
        ];
        $action = $this->generateUrl($this->getCurrentRouteName());

        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" class="form-inline" style="width: 760px;" action="{$action}" method="post">
  <div class="form-group">
    <label for="projectKey">Project</label>
    {$this->renderFormControlInputSelect($this->projectChoices,$projectKey,'projectKey','projectKey')}
  </div>
  <div class="form-group">
    <label for="sortBy">Sort By</label>
    {$this->renderFormControlInputSelect($sortByChoices,$formData['sortBy'],'sortBy','sortBy')}
  </div>
  <br/>
  <div class="form-group">
  <table><tr>
    <td>{$this->renderSearchCheckbox('dates[]',    'Days',   $dateChoices,   $formData['dates'])   }</td>
    <td>{$this->renderSearchCheckbox('programs[]','Programs',$programChoices,$formData['programs'])}</td>
    <td>{$this->renderSearchCheckbox('ages[]',    'Ages',    $ageChoices,    $formData['ages'])    }</td>
    <td>{$this->renderSearchCheckbox('genders[]', 'Genders', $genderChoices, $formData['genders']) }</td>
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
    protected function renderSearchCheckbox($name,$label,$choices,$values)
    {
        $style = 'text-align: center';

        $html = <<<EOD
<table>
  <tr><th colspan="30" style="{$style}">{$label}</th></tr>
    <td style="{$style}">All<br />
    <input type="checkbox" name="{$name}" class="cerad-checkbox-all" value="All" /></td>
EOD;
        foreach($choices as $label => $value) {
            $checked = in_array($value, $values) ? ' checked' : null;
            $html .= <<<EOD
    <td style="{$style}">{$label}<br />
    <input type="checkbox" name="{$name}" value="{$value}"{$checked} /></td>
EOD;
        }
        $html .= <<<EOD
  </tr>
</table>
</div>
EOD;
        return $html;
    }

}