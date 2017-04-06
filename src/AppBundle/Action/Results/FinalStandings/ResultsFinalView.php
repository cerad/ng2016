<?php

namespace AppBundle\Action\Results\FinalStandings;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Results\ResultsFinalCalculator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultsFinalView extends AbstractView2
{
    private $searchForm;
    private $finalCalculator;

    private $program;
    private $results;

    public function __construct(
        ResultsFinalSearchForm $searchForm,        
        ResultsFinalCalculator $finalCalculator
    )
    {
        $this->searchForm = $searchForm;
        $this->finalCalculator = $finalCalculator;
    }
    public function __invoke(Request $request)
    {
        $this->results = $request->attributes->get('results');

        return new Response($this->renderPage());
    }
    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $project = $this->getCurrentProject()['info'];

        $title = $project['title'];
        $content = <<<EOD
{$this->searchForm->render()}
<div id="layout-block">
<h1 class="text-center" style="font-style:italic; font-size:1.5em;"><emphasis>Congratulations to all {$title} Teams!</emphasis></h1>
</div>
{$this->renderStandings()}
EOD;

        return $this->renderBaseTemplate($content);
    }
    /* =======================================================================
     * The master pool section
     *
     */
    protected function renderStandings()
    {
        $html = null;

        if (!empty($this->results)){

            $standings = $this->generateStandings($this->results);

            $html .= <<<EOD
<legend class="float-right">Final Standings</legend>
<div class="container col-xs-8 col-xs-offset-2">
EOD;
            foreach ($standings as $div=>$teams) {
                $html .= $this->renderDivision($div, $teams);                    
            }
        }

        $html .= <<<EOD
        </div>
EOD;
    
        return $html;
    
    }
    protected function renderDivision($div, $teams)
    {
        $divisionTitle = str_replace('B',' Boys',$div);
        $divisionTitle = str_replace('G',' Girls',$divisionTitle);
        
        $html = <<<EOD
<div id="layout-block">
<legend class="float-right">{$divisionTitle}</legend>
<table class="standings" border = "1">
    <col>
<tr class="tbl-hdr">
    <th class="text-center" width="20%">Finish</th>
  <th class="text-center">Team</th>
</tr>
EOD;

        foreach($teams as $finish=>$team) {
            $html .= <<<EOD
<tr>
  <td class="text-center">{$finish}</td>
  <td class="text-left">{$this->renderTeamName($team)}</td>
</tr>
EOD;
        }
        $html .= <<<EOD
</table>
</div>

EOD;

        return $html;
    }
    private function renderTeamName($team) {
        if ($team->pointsScored !== null) {
            return $team->regTeamName;
        } else {
            return '';
        }

    }
    private function generateStandings($results)
    {
        $medalRounds = [];

        foreach($results as $game) {
                $medalRounds[$game->division][] = $game->getGames()[0];
        }
        
        $standings = $this->parseMedalRoundGames($medalRounds);
        
        return $standings;
    }
    protected function parseMedalRoundGames($medalRounds)
    {
        $standings = [];
        
        foreach($medalRounds as $div=>$games) {

            foreach($games as $game) {
                $teams = $game->getTeams();
                $homeTeam = $teams[1];
                $awayTeam = $teams[2];
                $homeGoals = $homeTeam->pointsScored;
                $awayGoals = $awayTeam->pointsScored;

                switch ($homeTeam->poolTeamSlotView) {
                    case 'SF1 Win':
                    case 'SF5 Win':
                        if ($homeGoals > $awayGoals) {
                            $standings[$div][1] = $homeTeam;
                            $standings[$div][2] = $awayTeam;
                        } else {
                            $standings[$div][1] = $awayTeam;
                            $standings[$div][2] = $homeTeam;
                        }
                        break;
                    case 'SF3 Win':
                    case 'SF5 Los':
                        if ($homeGoals > $awayGoals) {
                            $standings[$div][3] = $homeTeam;
                            $standings[$div][4] = $awayTeam;
                        } else {
                            $standings[$div][3] = $awayTeam;
                            $standings[$div][4] = $homeTeam;
                        }
                        break;
                    case 'SF1 Run':
                    case 'SF9 Win':
                        if ($homeGoals > $awayGoals) {
                            $standings[$div][5] = $homeTeam;
                            $standings[$div][6] = $awayTeam;
                        } else {
                            $standings[$div][5] = $awayTeam;
                            $standings[$div][6] = $homeTeam;
                        }
                        break;
                    case 'SF3 Run':
                    case 'SF9 Los':
                        if ($homeGoals > $awayGoals) {
                            $standings[$div][7] = $homeTeam;
                            $standings[$div][8] = $awayTeam;
                        } else {
                            $standings[$div][7] = $awayTeam;
                            $standings[$div][8] = $homeTeam;
                        }
                        break;
                    
                }
            }
        }

        return $standings;
    }
}
