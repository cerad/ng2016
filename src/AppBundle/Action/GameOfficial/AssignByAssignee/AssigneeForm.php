<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameReport2016\GameReport;

use AppBundle\Action\GameReport2016\GameReportRepository;
use Symfony\Component\HttpFoundation\Request;

class AssigneeForm extends AbstractForm
{
    /** @var  Game */
    private $game;
    
    /** @var  GameOfficial */
    private $gameOfficial;
    
    private $backRouteName;

    private $gameStatuses = [
        'Normal'            => 'Normal',
        'InProgress'        => 'In Progress',
        'Played'            => 'Played',
        'ForfeitByHomeTeam' => 'Forfeit By Home Team',
        'ForfeitByAwayTeam' => 'Forfeit By Away Team',
        'Cancelled'         => 'Cancelled',
        'Suspended'         => 'Suspended',
        'Terminated'        => 'Terminated',
        'StormedOut'        => 'Stormed Out',
        'HeatedOut'         => 'Heated Out',
    ];

    private $reportStates = [
        'Initial'   => 'Initial',  // Have a process to move to pending based on start time
        'Pending'   => 'Pending',
        'Submitted' => 'Submitted',
        'Verified'  => 'Verified',
        'Clear'     => 'Clear',
    ];

    public function __construct()
    {
    }
    public function setGame(Game $game)
    {
        $this->game = $game;
    }
    public function setGameOfficial(GameOfficial $gameOfficial)
    {
        $this->gameOfficial = $gameOfficial;
    }
    public function setBackRouteName($backRouteName)
    {
        $this->backRouteName = $backRouteName;
    }
    /**
     * @return GameOfficial
     */
    public function getGameOfficial()
    {
        return $this->gameOfficial;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();

        $homeTeamData = $data['gameReport']['homeTeam'];
        $awayTeamData = $data['gameReport']['awayTeam'];

        $gameReport = $this->gameReport;

        $homeTeam = $gameReport->homeTeam;
        $awayTeam = $gameReport->awayTeam;

        // Keep it simple for now
        $homeTeam->pointsAllowed = $this->filterScalarInteger($awayTeamData,'goalsScored');
        $homeTeam->pointsScored  = $this->filterScalarInteger($homeTeamData,'goalsScored');
        $homeTeam->sportsmanship = $this->filterScalarInteger($homeTeamData,'sportsmanship');
        $homeTeam->injuries      = $this->filterScalarInteger($homeTeamData,'injuries');

        $homeTeam->misconduct->playerWarnings  = $this->filterScalarInteger($homeTeamData,'playerWarnings');
        $homeTeam->misconduct->playerEjections = $this->filterScalarInteger($homeTeamData,'playerEjections');
        $homeTeam->misconduct->coachEjections  = $this->filterScalarInteger($homeTeamData, 'coachEjections');
        $homeTeam->misconduct->benchEjections  = $this->filterScalarInteger($homeTeamData, 'benchEjections');
        $homeTeam->misconduct->specEjections   = $this->filterScalarInteger($homeTeamData, 'specEjections');

        $awayTeam->pointsAllowed = $this->filterScalarInteger($homeTeamData,'goalsScored');
        $awayTeam->pointsScored  = $this->filterScalarInteger($awayTeamData,'goalsScored');
        $awayTeam->sportsmanship = $this->filterScalarInteger($awayTeamData,'sportsmanship');

        $awayTeam->misconduct->playerWarnings  = $this->filterScalarInteger($awayTeamData,'playerWarnings');
        $awayTeam->misconduct->playerEjections = $this->filterScalarInteger($awayTeamData,'playerEjections');
        $awayTeam->misconduct->coachEjections  = $this->filterScalarInteger($awayTeamData, 'coachEjections');
        $awayTeam->misconduct->benchEjections  = $this->filterScalarInteger($awayTeamData, 'benchEjections');
        $awayTeam->misconduct->specEjections   = $this->filterScalarInteger($awayTeamData, 'specEjections');

        // Misc stuff
        $gameReport->status      = $this->filterScalarString($data,'gameStatus');
        $gameReport->reportText  = $this->filterScalarString($data,'gameReportText');
        $gameReport->reportState = $this->filterScalarString($data,'gameReportState');

        // Require scores
        if (!$gameReport->hasScores()) {
            $errors['scores'][] = [
                'name' => 'scores',
                'msg'  => 'Scores must be entered.'
            ];
        }
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $game         = $this->game;
        $gameOfficial = $this->gameOfficial;

        $projectId  = $game->projectId;
        $gameNumber = $game->gameNumber;

        // Game  #11207: AYSO_U12B_Core, Thu, 10:30 AM on LL3
        $gameDescription = sprintf('Game #%d: %s, %s, %s On %s',
            $game->gameNumber,
            $game->homeTeam->poolView,
            $game->dow,
            $game->time,
            $game->fieldName
        );
        $gameDescription = $this->escape($gameDescription);

        $gameOfficialUpdateUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['projectId' => $projectId,'gameNumber' => $gameNumber, 'slot' => $gameOfficial->slot]
        );
        $backUrl  = $this->generateUrl($this->backRouteName);
        $backUrl .= '#game-' . $game->gameId;

        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;


        $html = <<<EOD
<form method="post" action="{$gameOfficialUpdateUrl}" class="form-horizontal">
<fieldset>
  <legend class="text-center">{$gameDescription}</legend>
</fieldset>
{$this->renderFormErrors()}
</form>
EOD;
        return $html;
    }

    private function renderPairHeaderRow()
    {
        $homeTeam = $this->gameReport->homeTeam;
        $awayTeam = $this->gameReport->awayTeam;

        $homeTeamName = $this->escape($homeTeam->regTeamName);
        $awayTeamName = $this->escape($awayTeam->regTeamName);

        $homeTeamSlotView = $this->escape($homeTeam->poolTeamView);
        $awayTeamSlotView = $this->escape($awayTeam->poolTeamView);

        return <<<EOD
<div class="row">
  <div class="col-xs-4"></div>
  <label class="col-xs-3 control-label text-center">Home<br/>{$homeTeamSlotView}<br/>{$homeTeamName}</label>
  <label class="col-xs-3 control-label text-center">Away<br/>{$awayTeamSlotView}<br/>{$awayTeamName}</label>
</div>
EOD;
    }
    /**
     * @param  $label 'Goals Scored'
     * @param  $name  'goalsScored'
     * @param  $homeTeamValue integer|null
     * @param  $awayTeamValue integer|null
     * @param  $placeHolder   string
     * @param  $readOnly      boolean
     * @return string
     */
    private function renderPairRow($label,$name,$homeTeamValue,$awayTeamValue,$placeHolder = '0',$readOnly = false)
    {
        $homeTeamName = sprintf('gameReport[homeTeam][%s]',$name);
        $awayTeamName = sprintf('gameReport[awayTeam][%s]',$name);

        $placeHolder = $readOnly ? 'readonly' : sprintf('placeholder="%s"',$placeHolder);

        return <<<EOD
<div class="row">
  <label class="col-xs-4 control-label">{$label}</label>
  <input type="number" name="{$homeTeamName}" value="{$homeTeamValue}" {$placeHolder} class="col-xs-3  form-control report-update">
  <input type="number" name="{$awayTeamName}" value="{$awayTeamValue}" {$placeHolder} class="col-xs-3  form-control report-update">
</div>
EOD;

    }
}
