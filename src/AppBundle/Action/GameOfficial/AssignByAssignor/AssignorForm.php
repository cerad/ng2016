<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignor;

use AppBundle\Action\AbstractForm;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\GameOfficial\AssignWorkflow;

use AppBundle\Action\GameOfficial\GameOfficialConflictsFinder;
use Symfony\Component\HttpFoundation\Request;

class AssignorForm extends AbstractForm
{
    /** @var  Game */
    private $game;

    /** @var  GameOfficial[] */
    private $gameOfficials = [];
    
    private $backRouteName;

    private $assignWorkflow;
    private $assignorFinder;
    private $conflictsFinder;
    
    private $gameOfficialChoices = [];

    public function __construct(
        AssignWorkflow $assignWorkflow, 
        AssignorFinder $assignorFinder,
        GameOfficialConflictsFinder $conflictsFinder
    ) {
        $this->assignWorkflow  = $assignWorkflow;
        $this->assignorFinder  = $assignorFinder;
        $this->conflictsFinder = $conflictsFinder;
    }
    public function setGame(Game $game)
    {
        $this->game = $game;

        $gameOfficials = $game->getOfficials();
        foreach($gameOfficials as $gameOfficial) {
            $this->gameOfficials[$gameOfficial->slot] = clone $gameOfficial;
        }
    }
    public function setBackRouteName($backRouteName)
    {
        $this->backRouteName = $backRouteName;
    }
    /**
     * @return GameOfficial[]
     */
    public function getGameOfficials()
    {
        return $this->gameOfficials;
    }
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return;
        }
        $this->isPost = true;

        $errors = [];

        $data = $request->request->all();

        $slots        = $data['slots'];
        $regPersonIds = $data['regPersonIds'];
        $assignStates = $data['assignStates'];

        foreach($slots as $slotIndex => $slot) {

            $gameOfficial = isset($this->gameOfficials[$slotIndex]) ? $this->gameOfficials[$slotIndex] : null;

            if ($gameOfficial) {

                $gameOfficial->regPersonId = $this->filterScalarString($regPersonIds, $slotIndex);
                $gameOfficial->assignState = $this->filterScalarString($assignStates, $slotIndex);

                $this->gameOfficials[$slotIndex] = $gameOfficial;

                $conflicts = $this->conflictsFinder->findGameOfficialConflicts($this->game, $gameOfficial);
                if (count($conflicts) > 0) {
                    $errors = array_merge($errors,$conflicts);
                }
            }
        }
        $this->formDataErrors = $errors;
    }

    public function render()
    {
        $game = $this->game;

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

        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        $homeTeamName = $this->escape($homeTeam->regTeamName);
        $awayTeamName = $this->escape($awayTeam->regTeamName);

        $gameOfficialUpdateUrl = $this->generateUrl(
            $this->getCurrentRouteName(),
            ['projectId' => $projectId,'gameNumber' => $gameNumber]
        );

        $backUrl  = $this->generateUrl($this->backRouteName);
        $backUrl .= '#game-' . $game->gameId;

        $this->gameOfficialChoices = array_merge(
            [null => 'Select Game Official'],
            $this->assignorFinder->findGameOfficialChoices($game)
        );

        $html = <<<EOD
<table style="min-width: 500px;">
  <tr><th colspan="3">Assign By User</th></tr>
  <tr><th colspan="3">{$gameDescription}</th></tr>
  <tr><th colspan="3">{$homeTeamName} -VS- {$awayTeamName}</th></tr>
</table>
<br>
<form method="post" action="{$gameOfficialUpdateUrl}" class="form-inline role="form"">
EOD;
        foreach($this->gameOfficials as $gameOfficial) {
            $html .= $this->renderGameOfficial($gameOfficial);
        }
        $html .= <<<EOD
  <button type="submit" class="btn bth-sm btn-primary">Update</button>
  <a href="{$backUrl}" class="btn bth-sm btn-default" ><span class="glyphicon glyphicon-chevron-left"></span>Back To Schedule</a>
{$this->renderFormErrors()}
</form>
EOD;
        return $html;
    }
    private function renderGameOfficial(GameOfficial $gameOfficial)
    {
        $slot = $gameOfficial->slot;

        $slotName = sprintf('slots[%s]',$slot);

        $regPersonIdName = sprintf('regPersonIds[%s]',$slot);
        $regPersonIdId   = sprintf('regPersonIds_%s', $slot);
        $assignStateName = sprintf('assignStates[%s]',$slot);
        $assignStateId   = sprintf('assignStates_%s', $slot);

        $assignState        = $gameOfficial->assignState;
        $assignTransitions  = $this->assignWorkflow->assignorStateTransitions;
        $assignStateChoices = $this->assignWorkflow->getStateChoices($assignState,$assignTransitions);

        return <<<EOD
  <div class="form-group">
    <input type="text" name="{$slotName}" readonly size="4" value="{$gameOfficial->slotView}" />
  </div>
  <div class="form-group">
      {$this->renderInputSelect($this->gameOfficialChoices,$gameOfficial->regPersonId,$regPersonIdName,$regPersonIdId)}
  </div>
  <div class="form-group">
      {$this->renderInputSelect($assignStateChoices,$gameOfficial->assignState,$assignStateName,$assignStateId)}
  </div> 
  <br><br>
EOD;
    }
    protected function renderFormErrors()
    {
        $html = null;
        foreach($this->formDataErrors as $conflict) {
            $html .= <<<EOD
<div class="errors">Conflicts With: {$conflict['gameNumber']} {$conflict['start']} {$conflict['fieldName']} {$conflict['gameOfficialName']} </div>
EOD;

        }
        return $html;
    }
}
