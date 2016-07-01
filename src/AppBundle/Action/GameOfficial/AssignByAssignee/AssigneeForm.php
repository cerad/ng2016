<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\GameOfficial\GameOfficialConflictsFinder;

use AppBundle\Action\RegPerson\RegPersonFinder;
use Symfony\Component\HttpFoundation\Request;

class AssigneeForm extends AbstractForm
{
    /** @var  Game */
    private $game;

    /** @var  GameOfficial */
    private $gameOfficial;

    private $backRouteName;

    private $assignWorkflow;
    private $assigneeFinder;
    private $conflictsFinder;
    private $regPersonFinder;

    public function __construct(
        AssignWorkflow $assignWorkflow,
        AssigneeFinder $assigneeFinder,
        GameOfficialConflictsFinder $conflictsFinder,
        RegPersonFinder $regPersonFinder
    )
    {
        $this->assignWorkflow  = $assignWorkflow;
        $this->assigneeFinder  = $assigneeFinder;
        $this->conflictsFinder = $conflictsFinder;
        $this->regPersonFinder = $regPersonFinder;
    }

    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    public function setGameOfficial(GameOfficial $gameOfficial)
    {
        $this->gameOfficial = clone $gameOfficial;
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

        $gameOfficial = $this->gameOfficial;

        $gameOfficial->regPersonId = $this->filterScalarString($data, 'regPersonId');
        $gameOfficial->assignState = $this->filterScalarString($data, 'assignState');

        $conflicts = $this->conflictsFinder->findGameOfficialConflicts($this->game, $gameOfficial);
        if (count($conflicts) > 0) {
            $errors = array_merge($errors,$conflicts);
        }
        if (!$this->isGranted('edit',$gameOfficial)) {
            $errors[] = 'Not approved to referee';
        }

        $this->formDataErrors = $errors;
    }

    public function render()
    {
        // The current user must be approved to referee
        $userRegPersonId = $this->getUserRegPersonId();
        if (!$this->regPersonFinder->isApprovedForRole('ROLE_REFEREE',$userRegPersonId)) {
            return $this->renderNotApproved($this->game);
        }
        $game = $this->game;
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
            ['projectId' => $projectId, 'gameNumber' => $gameNumber, 'slot' => $gameOfficial->slot]
        );
        $backUrl = $this->generateUrl($this->backRouteName);
        $backUrl .= '#game-' . $game->gameId;

        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        $homeTeamName = $this->escape($homeTeam->regTeamName);
        $awayTeamName = $this->escape($awayTeam->regTeamName);

        $assignState = $gameOfficial->assignState;
        $assignTransitions = $this->assignWorkflow->assigneeStateTransitions;
        $assignStateChoices = $this->assignWorkflow->getStateChoices($assignState, $assignTransitions);

        if ($assignState === 'Open') {
            $assignState = 'Requested';
        }
        $gameOfficialChoices = $this->assigneeFinder->findCrewChoices($this->getUser()->getRegPersonId());
        $gameOfficialChoicesApproved = [];
        foreach($gameOfficialChoices as $regPersonId => $regPersonName) {
            if ($this->regPersonFinder->isApprovedForRole('ROLE_REFEREE',$regPersonId)) {
                $gameOfficialChoicesApproved[$regPersonId] = $regPersonName;
            }
            // TODO Maybe check for conflicts here
        }
        $gameOfficialChoicesApproved = array_filter($gameOfficialChoices, function($regPersonId) {
            return $this->regPersonFinder->isApprovedForRole('ROLE_REFEREE',$regPersonId);
        }, ARRAY_FILTER_USE_KEY);

        // TODO maybe deal with no approved choices better

        $html = <<<EOD
  <legend>Self-Assignment By Referee</legend>
<table class="min-width-500">
  <tr><th colspan="3">{$gameDescription}</th></tr>
  <tr><th colspan="3">{$homeTeamName} -VS- {$awayTeamName}</th></tr>
</table>
<br/>
<form method="post" action="{$gameOfficialUpdateUrl}" class="form-inline role="form"">
<div class="col-xs-12 col-xs-offset-1">
  <div class="form-group col-xs-7">
    <input type="text" name="slot" readonly size="4" value="{$gameOfficial->slotView}" />

      {$this->renderInputSelect($gameOfficialChoicesApproved, $gameOfficial->regPersonId, 'regPersonId', 'regPersonId')}

      {$this->renderInputSelect($assignStateChoices, $assignState, 'assignState', 'assignState')}
  </div>
  <div class="form-group col-xs-4">
        <button type="submit" class="btn btn-sm btn-primary">Submit</button>
        <a href="{$backUrl}" class="btn bth-sm btn-default" ><span class="glyphicon glyphicon-chevron-left"></span>Back To Schedule</a>
    </div>
</div>
<br>
{$this->renderFormErrors()}
</form>
</br>
EOD;
        return $html;
    }
    private function renderNotApproved(Game $game)
    {
        $backUrl = $this->generateUrl($this->backRouteName);
        $backUrl .= '#game-' . $game->gameId;

        return <<<EOD
<legend class="text-warning">You are not currently approved to referee</legend>
<div class="app_help">
  <ul class="cerad-common-help ul_bullets">
    <li>The assignor needs to review your certifications and approve you.</li>
    <li>Contact the assignor (Tom Tobin, spsoccerref@earthlink.net) to expedite the process.</li>
  </ul>
</div>
<a href="{$backUrl}" class="btn bth-sm btn-default" ><span class="glyphicon glyphicon-chevron-left"></span>Back To Schedule</a>

EOD;
    }
    protected function renderFormErrors()
    {
        $html = null;
        foreach($this->formDataErrors as $error) {
            if (isset($error['gameNumber'])) {
                $conflict = $error;
                $html .= <<<EOD
<div class="errors">Conflicts With: {$conflict['gameNumber']} {$conflict['start']} {$conflict['fieldName']} </div>
EOD;
            } else {
                $html .= <<<EOD
<div class="errors">{$error} </div>
EOD;
            }
        }
        return $html;
    }
}
