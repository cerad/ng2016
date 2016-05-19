<?php

namespace AppBundle\Action\Schedule2016\Official;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleTemplate;

use Symfony\Component\HttpFoundation\Request;

class ScheduleOfficialView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;

    private $project;
    private $search;
    private $searchControls;
    private $currentRouteName;

    private $searchForm;
    private $scheduleTemplate;

    public function __construct(
        ScheduleOfficialSearchForm $searchForm,
        ScheduleTemplate $scheduleTemplate
    )
    {
        $this->searchForm = $searchForm;
        $this->scheduleTemplate = $scheduleTemplate;
    }
    public function __invoke(Request $request)
    {
        $this->currentRouteName = $request->attributes->get('_route');

        $this->games  = $request->attributes->get('games');
        $this->search = $request->attributes->get('search');

        $this->project = $this->getCurrentProjectInfo();

        $this->searchControls = $this->project['search_controls'];

        return $this->newResponse($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $content = <<<EOD
{$this->searchForm->render()}
<hr>
{$this->scheduleTemplate->setTitle('Official Game Schedule')}
EOD;
        $script = <<<EOD
<script type="text/javascript">
$(document).ready(function() {
    // checkbox all functionality
    $('.cerad-checkbox-all').change(Cerad.checkboxAll);
});
</script>
EOD;

        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        $baseTemplate->addScript ($script);
        return $baseTemplate->render();
    }
    /* ==================================================================
     * Render Search Form
     */
    protected function renderSearchForm()
    {
        $html = <<<EOD
<div class="container">
<form method="post" action="{$this->generateUrl($this->currentRouteName)}" class="cerad_common_form1">
  <fieldset>
    <table id="schedule-select"><tr>
EOD;
        foreach($this->searchControls as $key => $params) {

            $label = $params['label'];
            $html .= <<<EOD
    <td>{$this->renderSearchCheckbox($key, $label, $this->project[$key], $this->search[$key])}</td>
EOD;
        }
        $xlsUrl = $this->generateUrl($this->currentRouteName,['_format' => 'xls']);
        $csvUrl = $this->generateUrl($this->currentRouteName,['_format' => 'csv']);
        $shareSpan = '<span class="glyphicon glyphicon-share"></span>';

        $html .= <<<EOD
    </tr></table>
    <div class="col-xs-10">
      <div class="row float-right">
        <button type="submit" id="form_search" class="btn btn-sm btn-primary submit">Search</button>
        <a href="{$xlsUrl}" class="btn btn-sm btn-primary">{$shareSpan}Export to Excel</a>
        <a href="{$csvUrl}" class="btn btn-sm btn-primary">{$shareSpan}Export to CSV</a>
      </div>
    </div>
    <div class="clear-both"></div>
    <br/>
    <legend></legend>
  </fieldset>
</form>
EOD;
        return $html;
    }
    protected function renderSearchCheckbox($name,$label,$items,$selected)
    {
        $html = <<<EOD
<table>
  <tr><th colspan="30">{$label}</th></tr>
    <td align="center">All<br />
    <input type="checkbox" name="search[{$name}][]" class="cerad-checkbox-all" value="All" /></td>
EOD;
        foreach($items as $value => $label) {
            $checked = in_array($value, $selected) ? ' checked' : null;
            $html .= <<<EOD
    <td align="center">{$label}<br />
    <input type="checkbox" name="search[{$name}][]" value="{$value}"{$checked} /></td>
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
