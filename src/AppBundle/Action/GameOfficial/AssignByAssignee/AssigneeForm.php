<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameOfficial\AssignWorkflow;
use AppBundle\Action\GameOfficial\GameOfficialConflictsFinder;

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

    public function __construct(
        AssignWorkflow $assignWorkflow,
        AssigneeFinder $assigneeFinder,
        GameOfficialConflictsFinder $conflictsFinder
    )
    {
        $this->assignWorkflow = $assignWorkflow;
        $this->assigneeFinder = $assigneeFinder;
        $this->conflictsFinder = $conflictsFinder;
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
        
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $game = $this->game;
        $gameOfficial = $this->gameOfficial;

        $projectId = $game->projectId;
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
        // TODO Might need further processing to verify
        // $gameOfficialChoices = $this->assigneeFinder->findCrew($this->getUser(), $gameOfficial);
        $gameOfficialChoices = $this->assigneeFinder->findCrewChoices($this->getUser()->getRegPersonId());

        $html = <<<EOD
<table class="min-width-500">
  <tr><th colspan="3">Assign By User</th></tr>
  <tr><th colspan="3">{$gameDescription}</th></tr>
  <tr><th colspan="3">{$homeTeamName} -VS- {$awayTeamName}</th></tr>
</table>
<br/>
<form method="post" action="{$gameOfficialUpdateUrl}" class="form-inline role="form"">
<div class="col-xs-12 col-xs-offset-1">
  <div class="form-group col-xs-7">
    <input type="text" name="slot" readonly size="4" value="{$gameOfficial->slotView}" />

      {$this->renderInputSelect($gameOfficialChoices, $gameOfficial->regPersonId, 'regPersonId', 'regPersonId')}

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

    protected function renderFormErrors()
    {
        $html = null;
        foreach($this->formDataErrors as $conflict) {
            $html .= <<<EOD
<div class="errors">Conflicts With: {$conflict['gameNumber']} {$conflict['start']} {$conflict['fieldName']} </div>
EOD;

        }
        return $html;
    }
}
