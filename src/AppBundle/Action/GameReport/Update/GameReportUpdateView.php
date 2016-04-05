<?php
namespace AppBundle\Action\GameReport\Update;

use AppBundle\Action\AbstractTemplate;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameReportUpdateView extends AbstractTemplate
{
    private $gameStatuses;
    private $reportStatuses;
    
    private $scheduleURL;    

    public function __construct()
    {
        $this->gameStatuses = [
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
        $this->reportStatuses = [
            'Pending'   => 'Pending',
            'Submitted' => 'Submitted',
            'Verified'  => 'Verified',
            'Clear'     => 'Clear',
        ];
        
        if (!empty($_SESSION["RETURN_TO_URL"]) ) {
            $this->scheduleURL = $_SESSION["RETURN_TO_URL"];
        } else {
            $this->scheduleURL = "#";
        }

    }
    public function __invoke(Request $request)
    {
        $gameReport = $request->attributes->get('gameReport');
        
        $content = <<<EOD
{$this->renderForm($gameReport)}
<br />
{$this->renderScoringNotes()}
EOD;
        $this->baseTemplate->setContent($content);

        return new Response($this->baseTemplate->render());
    }
    protected function renderForm($gameReport)
    {
        $game = $gameReport['game'];

        $gameNumber     = $game['number'];
        $gameNumberNext = $gameNumber + 1;
        
        $gameReportUpdateUrl = $this->generateUrl('game_report_update',['gameNumber' => $gameNumber]);

        $homeTeamReport = $gameReport['teamReports'][1];
        $awayTeamReport = $gameReport['teamReports'][2];

        $homeTeam = $homeTeamReport['team'];
        $awayTeam = $awayTeamReport['team'];

        $homeTeamReportPrefix = 'gameReport[teamReports][1]';
        $awayTeamReportPrefix = 'gameReport[teamReports][2]';
        
        $html = <<<EOD
<div class="container">
<form method="post" action="{$gameReportUpdateUrl}" class="cerad_common_form1 form-horizontal">
      <fieldset>
        <legend class="text-center">{$this->escape($gameReport['desc'])}</legend> <!-- Game Report -->

        <div class="form-group">
          <div class="col-xs-2">
            <!-- required for floating -->
            <!-- Nav tabs -->

            <ul class="nav nav-tabs tabs-left">
              <li class="active"><a href="#score" data-toggle="tab">Score</a></li>

              <li><a href="#misconduct" data-toggle="tab">Misconduct</a></li>

              <li><a href="#injuries" data-toggle="tab">Injuries</a></li>

              <li><a href="#notes" data-toggle="tab">Notes</a></li>

              <li><a href="#showAll" data-toggle="tab">Show All</a></li>
            </ul>
          </div>

          <div class="col-xs-10">
            <!-- Tab panes -->

            <div class="tab-content">          
                
              <div class="tab-pane active" id="score">
                  
                    <div class="row">
                        <div class="col-xs-4"></div> 
                        <label class="col-xs-3 control-label text-center" >Home: {$homeTeam['groupSlot']}<br/>{$homeTeam['name']}</label> 
                        <label class="col-xs-3 control-label text-center" >Away: {$awayTeam['groupSlot']}<br/>{$awayTeam['name']}</label> 
                    </div>      
        
                    <div class="row">
                        <label class="col-xs-4 control-label">Goals Scored</label>
                        <input type="number" name="{$homeTeamReportPrefix}[goalsScored]" value="{$homeTeamReport['goalsScored']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[goalsScored]" value="{$awayTeamReport['goalsScored']}" placeholder="0" class="col-xs-3 entry">
                    </div>
        
                    <div class="row">
                      <label class="col-xs-4 control-label">Sportsmanship</label>
                      <input type="number" name="{$homeTeamReportPrefix}[sportsmanship]" value="{$homeTeamReport['sportsmanship']}" placeholder="40" class="col-xs-3 entry">
                      <input type="number" name="{$awayTeamReportPrefix}[sportsmanship]" value="{$awayTeamReport['sportsmanship']}" placeholder="40" class="col-xs-3 entry">
                    </div>
                  
                    <div class="row">
                      <label class="col-xs-4 control-label">Points Earned</label>
                      <input type="number" name="{$homeTeamReportPrefix}[pointsEarned]" value="{$homeTeamReport['pointsEarned']}" readonly="readonly" class="col-xs-3 entry">
                      <input type="number" name="{$awayTeamReportPrefix}[pointsEarned]" value="{$awayTeamReport['pointsEarned']}" readonly="readonly" class="col-xs-3 entry">
                    </div>
        
                    <div class="row">
                      <label class="col-xs-4 control-label">Points Minus</label>
                      <input type="number" name="{$homeTeamReportPrefix}[pointsMinus]" value="{$homeTeamReport['pointsMinus']}" readonly="readonly" class="col-xs-3 entry">
                      <input type="number" name="{$awayTeamReportPrefix}[pointsMinus]" value="{$awayTeamReport['pointsMinus']}" readonly="readonly" class="col-xs-3 entry">
                    </div>
                </div>
        
                <div class="tab-pane" id="misconduct">
                    <div class="row">
                        <div class="col-xs-4"></div> 
                        <label class="col-xs-3 control-label text-center" >Home: {$homeTeam['groupSlot']}<br/>{$homeTeam['name']}</label> 
                        <label class="col-xs-3 control-label text-center" >Away: {$awayTeam['groupSlot']}<br/>{$awayTeam['name']}</label> 
                    </div>      
        
                    <div class="row">
                        <label class="col-xs-4 control-label">Player Cautions</label>            
                        <input type="number" name="{$homeTeamReportPrefix}[playerWarnings]" value="{$homeTeamReport['playerWarnings']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[playerWarnings]" value="{$awayTeamReport['playerWarnings']}" placeholder="0" class="col-xs-3 entry">
                    </div>
                    
                    <div class="row">
                        <label class="col-xs-4 control-label">Player Send-Offs</label>
                        <input type="number" name="{$homeTeamReportPrefix}[playerEjections]" value="{$homeTeamReport['playerEjections']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[playerEjections]" value="{$awayTeamReport['playerEjections']}" placeholder="0" class="col-xs-3 entry">
                    </div>
                    
                    <div class="row">
                        <label class="col-xs-4 control-label">Coach Ejections</label>
                        <input type="number" name="{$homeTeamReportPrefix}[coachEjections]" value="{$homeTeamReport['coachEjections']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[coachEjections]" value="{$awayTeamReport['coachEjections']}" placeholder="0" class="col-xs-3 entry">
                    </div>
                    
                    <div class="row">
                        <label class="col-xs-4 control-label">Substitute Ejections</label>
                        <input type="number" name="{$homeTeamReportPrefix}[benchEjections]" value="{$homeTeamReport['benchEjections']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[benchEjections]" value="{$awayTeamReport['benchEjections']}" placeholder="0" class="col-xs-3 entry">
                    </div>
                    
                    <div class="row">
                        <label class="col-xs-4 control-label">Spectator Ejections</label>
                        <input type="number" name="{$homeTeamReportPrefix}[specEjections]" value="{$homeTeamReport['specEjections']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[specEjections]" value="{$awayTeamReport['specEjections']}" placeholder="0" class="col-xs-3 entry">
                    </div>
                </div>
        
                <div class="tab-pane" id="injuries">
                    <div class="row">
                        <div class="col-xs-4"></div> 
                        <label class="col-xs-3 control-label text-center" >Home: {$homeTeam['groupSlot']}<br/>{$homeTeam['name']}</label> 
                        <label class="col-xs-3 control-label text-center" >Away: {$awayTeam['groupSlot']}<br/>{$awayTeam['name']}</label> 
                    </div>      
        
                    <div class="row">
                        <label class="col-xs-4 control-label">Serious Injuries</label>
                        <input type="number" name="{$homeTeamReportPrefix}[injuries]" value="{$homeTeamReport['injuries']}" placeholder="0" class="col-xs-3 entry">
                        <input type="number" name="{$awayTeamReportPrefix}[injuries]" value="{$awayTeamReport['injuries']}" placeholder="0" class="col-xs-3 entry">            
                    </div>
                </div>

                <div class="tab-pane" id="notes">
                  <div class="row">
                    <label class="col-xs-4 control-label">Notes</label> 
                    <textarea name="gameReport[notes]" rows="10" cols="48" wrap="hard" class="textarea">{$this->escape($gameReport['notes'])}</textarea>
                  </div>
                </div>
        
                <div class="tab-pane" id="showAll">
                  <p>Maybe have a tab to show everything ??</p>
                </div>
              </div>
            </div>
          </div>
          <legend></legend>
          <div class="col-xs-11">
          <div class="row float-right">
                  <button type="submit" name="save" class="btn btn-sm btn-primary submit" ><span class="glyphicon glyphicon-save"></span> Save</button>
                  <button type="submit" name="next" class="btn btn-sm btn-primary submit active"><span class="glyphicon glyphicon-arrow-right"></span> Save Then Next</button>
                  <a href="{$this->scheduleURL}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-share-alt"></span> Return to Schedule</a>
          </div>
        </div>
        <div class="col-xs-10">
        <div class="col-xs-8 col-xs-offset-7">
          <div class="row">
                <label class="col-xs-4 control-label">Next Match Number</label> 
              <input class="col-xs-3 entry" type="number" name="nextGameNumber" value="{$gameNumberNext}" />
            </div>
        </div>
        </div>
          <div class="clear-both"></div>       
      </fieldset>

      <fieldset>
           <div class="form-group">
          <div class="col-xs-12">
              <div class="row">
                  <label class="col-xs-2 control-label" for="gameStatus">Game Status</label>
                  <select class="col-xs-3 entry" id="gameStatus" name="gameReport[game][status]">
EOD;
        $status = $game['status'];
        foreach($this->gameStatuses as $value => $text) {
            $selected = $status == $value ? ' selected' : null;
            $html .= <<<EOD
      <option{$selected} value="{$value}">{$text}</option>
EOD;
        }
        $html .= <<<EOD
      </select>
               
                  <label class="col-xs-2 control-label" for="gameReportStatus">Report Status</label> 
    <select class="col-xs-3 entry" id="gameReportStatus" name="gameReport[status]">
EOD;
        $status = $gameReport['status'];
        foreach($this->reportStatuses as $value => $text) {
            $selected = $status == $value ? ' selected' : null;
            $html .= <<<EOD
      <option{$selected} value="{$value}">{$text}</option>
EOD;
        }
        $html .= <<<EOD
      </select>
               </div>
          </div>
          </div>
        </fieldset>      
</form>
</div> <!-- .container -->
EOD;
        return $html;
    }
    /* ====================================================
     * The help section
     */
    protected function renderScoringNotes()
    {
        include 'GameReportUpdateNotes.php';
        
        return $notes;
    }
}