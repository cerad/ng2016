<?php

namespace AppBundle\Action\Results\FinalStandings;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;
use AppBundle\Action\Results\FinalStandings\Calculator\FinalCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsFinalView extends AbstractView
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    /** @var  StandingsCalculator */
    private $finalCalculator;

    private $standings;
    private $level;

    public function __construct(
        ScheduleRepository  $scheduleRepository,
        FinalCalculator $finalCalculator
    )
    {
        $this->scheduleRepository  = $scheduleRepository;
        $this->finalCalculator = $finalCalculator;
    }
    public function __invoke(Request $request)
    {
        $criteria = [];

        $this->project = $request->attributes->get('project');
        $criteria = $request->attributes->get('criteria');
 
        $games = count($criteria) > 1 ? $this->scheduleRepository->findProjectGames($criteria) : [];
 
        if (!empty($games)){
            $level = array_values($games)[0]['level_key'];
            $level = str_replace('_',' ',$level);
        }

        $this->standings = $this->finalCalculator->generateStandings($games);
   
        return new Response($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $title = $this->project['title'];
    
        $content = <<<EOD
<div id="layout-block">
<h1 class="text-center" style="font-style:italic; font-size:1.5em;"><emphasis>Congratulations to all {$title} Teams!</emphasis></h1>
<hr>

<br />
</div>
{$this->renderStandings()}
EOD;

        $baseTemplate = $this->baseTemplate;
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
    /* =======================================================================
     * The master pool section
     *
     */
    protected function renderStandings()
    {
        $html = null;

        if (!empty($this->standings)){
            foreach ($this->standings['Core'] as $div=>$teams) {
                $html .= $this->renderDivision($div, $teams);                    
            }
            foreach ($this->standings['Extra'] as $div=>$teams) {
                $html .= $this->renderDivision($div, $teams);                    
            }
        }
        
        return $html;
    }
    protected function renderDivision($div, $teams)
    {
        $html = <<<EOD
<div id="layout-block">
<legend class="float-right">Final Standings : {$div} </legend>

<table class="standings" border = "1">
<tr class="tbl-hdr">
    <th class="text-center">Finish</th>
  <th class="text-center">Team</th>

</tr>
EOD;
        foreach($teams as $finish=>$teamName) {
            $html .= <<<EOD
<tr>
  <td class="text-center">{$finish}</td>
  <td class="text-left">{$teamName}</td>
</tr>
EOD;
        }
        $html .= <<<EOD
</table>
</div>

EOD;

        return $html;
    }


}
