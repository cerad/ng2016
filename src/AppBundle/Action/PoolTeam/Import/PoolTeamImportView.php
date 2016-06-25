<?php
namespace AppBundle\Action\PoolTeam\Import;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class PoolTeamImportView extends AbstractView2
{
    private $form;

    /** @var  PoolTeamImportResults */
    private $results;

    public function __construct(PoolTeamImportForm $form)
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
<hr>
{$this->renderResults()}
<ul>
  <li>Add a tilde (~) in front of pool team key to delete the pool team.</li>
  <li>Any games using the pool team key must be deleted first.</li>
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
<tr><td>Updated</td><td>{$results->updatedCount}</td></tr>
</table>
EOD;
        if (count($results->existingGames) < 1) {
            return $html;
        }
        foreach($results->existingGames as $game) {
            $html .= sprintf("<div>%s %s</div>\n",$game['poolTeamId'],$game['gameNumber']);
        }
        return $html;
    }
}