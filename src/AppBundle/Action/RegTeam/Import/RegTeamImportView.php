<?php
namespace AppBundle\Action\RegTeam\Import;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class RegTeamImportView extends AbstractView2
{
    private $form;

    /** @var  RegTeamImportResults */
    private $results;

    public function __construct(RegTeamImportForm $form)
    {
        $this->form = $form;
    }
    public function __invoke(Request $request)
    {
        $this->results = $request->attributes->get('results');

        return $this->newResponse($this->renderPage());
    }
    private function renderPage()
    {
        $content = <<<EOD
<div id="layout-block">
{$this->form->render()}
</div>
{$this->renderResults()}
<legend>Notes on Import</legend>
<ul class="cerad_common_help ul_bullets">
  <li>Add a tilde (~) in front of reg team key to delete the reg team.</li>
  <li>Add a tilde (~) in front of pool team key to clear the link with reg team.</li>
  <li>Region can either be a number or AYSOR:0894 or blank.</li>
  <li>Leave SARS column blank to generate from region.</li>
  <li>Add the literal string SARS to team name, it will be replaced with generated SARS</li>
  <li>Add the literal string SARS to team name, it will be replaced with generated SARS</li>
  <li>"Team Key" field should be space separated Gender, Age, Program, and team number like "G 19U Core 1"</li>
  <li>"Team Key" and "Pool Team Key" fields should be space separated Gender, Age, Program, poolType, 
  poolTeamSlotView like "G 19U Core PP A 1"</li>
  <li><b><i>NOTE: Very little error checking is included so ill-formed or duplicate keys will have unpredictable 
  results.</i></b></li>
</ul>
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderResults()
    {
        if (!$this->results) return null;
        $results = $this->results;
        
        $html = <<<EOD
<table>
<tr><td>File   </td><td>{$results->fileName}</td></tr>
<tr><td>Total  </td><td>{$results->totalCount}</td></tr>
<tr><td>Deleted</td><td>{$results->deletedCount}</td></tr>
<tr><td>Created</td><td>{$results->createdCount}</td></tr>
<tr><td>Updated Reg  Teams</td><td>{$results->updatedCount}</td></tr>
<tr><td>Updated Pool Teams</td><td>{$results->updatedPoolTeamCount}</td></tr>
</table>
<hr>
EOD;
        return $html;
    }
}
