<?php
namespace AppBundle\Action\Game\Import;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class GameImportView extends AbstractView2
{
    private $form;

    /** @var  GameImportResults */
    private $results;

    public function __construct(GameImportForm $form)
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
<legend>Notes on Import</legend>
<ul class="cerad_common_help ul_bullets">
  <li>Add a minus sign (-) in front of game number to delete the game.</li>
</ul>

EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderResults()
    {
        if (!$this->results) return null;
        $results = $this->results;
        return <<<EOD
<table>
<tr><td>File   </td><td>{$results->fileName}</td></tr>
<tr><td>Total  </td><td>{$results->totalCount}</td></tr>
<tr><td>Deleted</td><td>{$results->deletedCount}</td></tr>
<tr><td>Created</td><td>{$results->createdCount}</td></tr>
<tr><td>Updated</td><td>{$results->updatedCount}</td></tr>
</table>
EOD;

    }
}