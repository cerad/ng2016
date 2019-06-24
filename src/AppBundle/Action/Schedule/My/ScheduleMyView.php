<?php

namespace AppBundle\Action\Schedule\My;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Schedule\ScheduleGame;
use AppBundle\Action\Schedule\ScheduleTemplate;
use Symfony\Component\HttpFoundation\Request;

class ScheduleMyView extends AbstractView2
{
    /** @var  ScheduleGame[] */
    private $games;

    private $searchForm; // Keep for now, not being used
    private $scheduleTemplate;

    public function __construct(
        ScheduleMySearchForm $searchForm,
        ScheduleTemplate $scheduleTemplate
    ) {
        $this->searchForm = $searchForm;
        $this->scheduleTemplate = $scheduleTemplate;
    }

    public function __invoke(Request $request)
    {
        $this->games = $request->attributes->get('games');

        return $this->newResponse($this->renderPage());
    }

    /* ========================================================
     * Render Page
     */
    private function renderPage()
    {
        $gameCount = count($this->games);
        $certifications = null;

        $html =
            <<<EOT
<div id="clear-fix">
    <legend>Instructions for Referees</legend>
      <ul class="cerad-common-help ul_bullets">
            <li>Click on "<a href="{$this->generateUrl('schedule_official_2019')}">Request Assignments</a>" under the "Referees" menu item above.</li>
            <li>On any open match, click on the position you'd like to request, e.g. REF, AR1, AR2</li>
            <li>Click "Submit" button"</li>
            <li>Check back on your schedule under "<a href="{$this->generateUrl('schedule_my_2019')}">My Schedule</a>" under the "My Stuff" menu item above to see the assignments.
            <li>Detailed instructions for self-assigning are available <a href="{$this->generateUrl(
                'detailed_instruction'
            )}" target="_blank">by clicking here</a>.</ul>
      </ul>
</div>
<hr>
EOT;


        $html .= <<<EOD
<div class="schedule-search col-xs-8 col-xs-offset-2 clearfix">
    <a href="{$this->generateUrl('schedule_my_2019', ['_format' => 'xls'])}" class="btn btn-sm btn-primary pull-right"><span class="glyphicon glyphicon-share"></span> Export to Excel</a>
</div>
<table id="schedule" class="schedule">
  <thead>
    <tr><th colspan="20" class="text-center">My Game Schedule - Game Count: $gameCount </th></tr>
    <tr>
      <th class="schedule-game" >Game</th>
      <th class="schedule-dow"  >Date</th>
      <th class="schedule-dow"  >DOW</th>
      <th class="schedule-time" >Time</th>
      <th class="schedule-field">Field</th>
      <th class="schedule-group">Group</th>
      <th class="schedule-slot" >Slot</th>
      <th class="schedule-teams">Home / Away</th>
      <th class="schedule-officials">Officials</th>
    </tr>
  </thead>
  <tbody>  
  {$this->renderScheduleRows($this->games)}
  </tbody>
</table>
</div>
<br />
</div
EOD;

        return $this->renderBaseTemplate($html);
    }

    /**
     * @param ScheduleGame[] $games
     * @return string
     */
    private function renderScheduleRows($games)
    {
        $html = null;
        foreach ($games as $game) {

            $ref = ucwords(strtolower($game->referee->regPersonName));
            $ar1 = ucwords(strtolower($game->ar1->regPersonName));
            $ar2 = ucwords(strtolower($game->ar2->regPersonName));
            $html .= <<<EOD
<tr>
  <td class="schedule-game" >{$game->gameNumber}</td>
  <td class="schedule-dow"  >{$game->dow}</td>
  <td class="schedule-dow"  >{$game->date}</td>
  <td class="schedule-time" >{$game->time}</td>
  <td class="schedule-field"><a href="{$this->generateUrl('field_map')}" target=_blank}>{$game->fieldName}</a></td>
  <td class="schedule-group">{$game->poolView}</td>
  <td>{$game->homeTeam->poolTeamSlotView}<hr class="separator">{$game->awayTeam->poolTeamSlotView}</td>
  <td class="text-left">{$game->homeTeam->regTeamName}&nbsp;<hr class="separator">{$game->awayTeam->regTeamName}&nbsp;
  </td>
  <td class="schedule-referees text-left" >
    <table>
      <tr><td style="text-align: left">{$game->referee->slotView}: {$ref}</td></tr >
      <tr><td style="text-align: left">{$game->ar1->slotView}: {$ar1}</td></tr >
      <tr><td style="text-align: left">{$game->ar2->slotView}: {$ar2}</td></tr >
    </table >
  </td >
</tr>
EOD;
        }

        return $html;
    }
}
