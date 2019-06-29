<?php
namespace AppBundle\Action\GameReport\Update;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\GameReport\GameReport;

use AppBundle\Action\GameReport\GameReportRepository;
use Symfony\Component\HttpFoundation\Request;

class GameReportUpdateForm extends AbstractForm
{
    /** @var  GameReport */
    private $gameReport;

    private $gameReportFinder;

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

    public function __construct(GameReportRepository $gameReportFinder)
    {
        $this->gameReportFinder = $gameReportFinder;
    }
    public function setGameReport(GameReport $gameReport)
    {
        $this->gameReport = $gameReport;
    }
    public function setBackRouteName($backRouteName)
    {
        $this->backRouteName = $backRouteName;
    }
    /**
     * @return GameReport
     */
    public function getGameReport()
    {
        return $this->gameReport;
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
        $awayTeam->injuries      = $this->filterScalarInteger($awayTeamData,'injuries');

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
        $gameReport = $this->gameReport;

        $projectId  = $gameReport->projectId;
        $gameNumber = $gameReport->gameNumber;

        $gameNumberNext = $this->gameReportFinder->doesGameExist($projectId,$gameNumber + 1) ? $gameNumber + 1 : $gameNumber;

        // Game Report #11207: AYSO_U12B_Core, Thu, 10:30 AM on LL3
        $gameReportDescription = sprintf('Game Report #%d: %s, %s, %s On %s',
            $gameReport->gameNumber,
            $gameReport->homeTeam->poolView,
            $gameReport->dow,
            $gameReport->time,
            $gameReport->fieldName
        );
        $gameReportDescription = $this->escape($gameReportDescription);

        $gameReportUpdateUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['projectId' => $projectId,'gameNumber' => $gameNumber]
        );
        $backUrl  = $this->generateUrl($this->backRouteName);
        $backUrl .= '#game-' . $gameReport->gameId;

        $homeTeam = $gameReport->homeTeam;
        $awayTeam = $gameReport->awayTeam;

        $homeTeamMisconduct = $homeTeam->misconduct;
        $awayTeamMisconduct = $awayTeam->misconduct;

        $html = <<<EOD
<form method="post" action="{$gameReportUpdateUrl}" class="form-horizontal">
<fieldset>
  <legend class="text-center">{$this->escape($gameReportDescription)}</legend> <!-- Game Report -->

  <div class="form-group">
    <div class="col-xs-2">
    <!-- required for floating -->
    <!-- Nav tabs -->

      <ul class="nav nav-tabs tabs-left">
        <li class="active"><a href="#score" data-toggle="tab">Score</a></li>
        <li><a href="#misconduct" data-toggle="tab">Misconduct</a></li>
        <li><a href="#injuries" data-toggle="tab">Injuries</a></li>
        <li><a href="#notes" data-toggle="tab">Notes</a></li>
      </ul>
    </div>

    <div class="col-xs-10">
      <!-- Tab panes -->
      <div class="tab-content">
        <div class="tab-pane active" id="score">
          {$this->renderPairHeaderRow()}
          {$this->renderPairRow('Goals Scored',   'goalsScored',   $homeTeam->pointsScored,  $awayTeam->pointsScored)}
          {$this->renderPairRow('Sportsmanship',  'sportsmanship', $homeTeam->sportsmanship, $awayTeam->sportsmanship,'40')}
          {$this->renderPairRow('Points Earned',  'pointsEarned',  $homeTeam->pointsEarned,  $awayTeam->pointsEarned,  null,true)}
          {$this->renderPairRow('Points Deducted','pointsDeducted',$homeTeam->pointsDeducted,$awayTeam->pointsDeducted,null,true)}
        </div>
        <div class="tab-pane" id="misconduct">
          {$this->renderPairHeaderRow()}
          {$this->renderPairRow('Player Cautions',     'playerWarnings', $homeTeamMisconduct->playerWarnings, $awayTeamMisconduct->playerWarnings)}
          {$this->renderPairRow('Player Send-Offs',    'playerEjections',$homeTeamMisconduct->playerEjections,$awayTeamMisconduct->playerEjections)}
          {$this->renderPairRow('Coach Ejections',     'coachEjections', $homeTeamMisconduct->coachEjections, $awayTeamMisconduct->coachEjections)}
          {$this->renderPairRow('Substitute Ejections','benchEjections', $homeTeamMisconduct->benchEjections, $awayTeamMisconduct->benchEjections)}
          {$this->renderPairRow('Spectator  Ejections','specEjections',  $homeTeamMisconduct->specEjections,  $awayTeamMisconduct->specEjections)}
        </div>
        <div class="tab-pane" id="injuries">
          {$this->renderPairHeaderRow()}
          {$this->renderPairRow('Serious Injuries','injuries',$homeTeam->injuries,$awayTeam->injuries)}
        </div>
        <div class="tab-pane" id="notes">
          <div class="row">
            <label class="col-xs-4 control-label">Notes</label>
            <textarea name="gameReportText" rows="10" cols="48" wrap="hard" class="textarea">{$this->escape($gameReport->reportText)}</textarea>
          </div>
        </div>
      </div>
    </div>
  </div>
EOD;

        if ($this->isGranted('ROLE_SCORE_ADMIN')) {
            $html .= <<<EOD
<fieldset>
  <div class="form-group">
    <div class="col-xs-12">
      <div class="row">
        <label class="col-xs-2 control-label" for="gameStatus">Game Status</label>
        <select class="col-xs-3 form-control report-update" id="gameStatus" name="gameStatus">
EOD;
            $status = $gameReport->status;
            foreach($this->gameStatuses as $value => $text) {
                $selected = $status == $value ? ' selected' : null;
                $html .= <<<EOD
          <option{$selected} value="{$value}">{$text}</option>
EOD;
            }
            $html .= <<<EOD
        </select>
        <label class="col-xs-2 control-label" for="gameReportState">Report Status</label>
        <select class="col-xs-3 form-control report-update" id="gameReportState" name="gameReportState">
EOD;
            $state = $gameReport->reportState;
            foreach($this->reportStates as $value => $text) {
                $selected = $state == $value ? ' selected' : null;
                $html .= <<<EOD
          <option{$selected} value="{$value}">{$text}</option>
EOD;
            }
        }
        $html .= <<<EOD
        </select>
      </div>
    </div>
  </div>
</fieldset>
EOD;
        
        $html .= <<<EOD
  <div class="col-xs-11">
    <div class="row float-right">
      <button type="submit" name="save" class="btn btn-sm btn-primary submit" >
        <span class="glyphicon glyphicon-save"></span> Save
      </button>
      <button type="submit" name="next" class="btn btn-sm btn-primary submit active">
        <span class="glyphicon glyphicon-arrow-right"></span> Save Then Next
      </button>
      <a href="{$backUrl}" class="btn btn-sm btn-primary">
        <span class="glyphicon glyphicon-share-alt"></span> Return to Schedule
      </a>
    </div>
  </div>
  <div class="col-xs-10">
    <div class="col-xs-8 col-xs-offset-7">
      <div class="row">
        <label class="col-xs-4 control-label">Next Match Number</label>
        <input class="col-xs-3  form-control report-update" type="number" name="nextGameNumber" value="{$gameNumberNext}" />
      </div>
    </div>
  </div>
  <div class="clear-both"></div>
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
