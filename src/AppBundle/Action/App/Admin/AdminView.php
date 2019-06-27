<?php

namespace AppBundle\Action\App\Admin;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class AdminView extends AbstractView2
{
    private $project;
    private $projectId;
    private $switchUserForm;
    
    public function __construct(
        AdminSwitchUserForm $switchUserForm
    ) {
        $this->switchUserForm = $switchUserForm;
    }
    public function __invoke(Request $request)
    {
        $this->project = $this->getCurrentProjectInfo();
        $this->projectId = $this->getCurrentProjectKey();

        return $this->newResponse($this->render());
    }

    /* Admin Page content */
    protected function render()
    {
        $content = <<<EOT
<div class="container no-disc">
<h3>Administrative Functions</h3>
EOT;
        $content .= $this->renderMatchReporting();
  
        $content .= $this->renderScheduleManagement();
  
        $content .= $this->renderRefereeManagement();
  
        $content .= $this->renderTeamManagement();
  
        $content .= $this->renderAccountManagement();
        
        $content .= $this->renderCommunications();
          
        $content .=  <<<EOT
</div> <!-- class="container no-disc" -->
<br>
<div class="panel-float-clear"></div>
EOT;
        $content .= $this->switchUserForm->render();
        $content .= $this->renderAdminHelp();

        return $this->renderBaseTemplate($content);
    }
    
    /* Match Reporting content */
    protected function renderMatchReporting()
    {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Match Reporting</h1>
  </div>
  <div class="panel-body">
    <ul>
EOT;
        if ($this->isGranted('ROLE_SCORE_ENTRY')) {
            $html .= <<<EOT
      <li><a href="{$this->generateUrl('game_report_update',['projectId' => $this->projectId,'gameNumber' => 11001])}">Enter Match Results</a></li>
EOT;
      }
      
        $html .= <<<EOT
      <li><a href="{$this->generateUrl('results_poolplay_2019')}">Pool Play</a></li>

      <li><a href="{$this->generateUrl('results_medalround_2019')}">Medal Round</a></li>

      <li><a href="{$this->generateUrl('results_sportsmanship_2019')}">Sportsmanship</a></li>

      <li><a href="{$this->generateUrl('results_final_2019')}">Final Standings</a></li>
    </ul>
  </div>
</div>

EOT;
      return $html;
    }

    protected function renderScheduleManagement()
    {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Schedule Management</h1>
  </div>
  <div class="panel-body">
    <ul>
        <li><a href="{$this->generateUrl('schedule_game_2019')}">View Game Schedule</a></li>
        <li><a href="{$this->generateUrl('schedule_team_2019')}">View Team Schedule</a></li>
        <li><a href="{$this->generateUrl('schedule_game_2019',['_format' => 'xls'])}">Export Game Schedule</a></li>

<!--    
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2019',['_format' => 'xls_qf'])}">Export Quarter-Finals Schedule for review</a></li>
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2019',['_format' => 'xls_sf'])}">Export Semi-Finals Schedule for review</a></li>
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2019',['_format' => 'xls_fm'])}">Export Finals Schedule for review</a></li>
-->
        <li><a href="{$this->generateUrl('field_map')}" target="_blank">Field Map</a></li>
EOT;

      $html .=
<<<EOT
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderTeamManagement()
    {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Team Management</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('game_listing')}">View Teams</a></li>
      <li><a href="{$this->generateUrl('reg_team_export2')}">Export Teams</a></li>
EOT;
      if ($this->isGranted('ROLE_ADMIN')) {
        $html .= <<<EOT
      <li><a href="{$this->generateUrl('reg_team_import2')}">Import/Update Teams</a></li>
EOT;
      }

      $html .= <<<EOT
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderRefereeManagement()
    {
      if ($this->isGranted('ROLE_ASSIGNOR')) {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Referee Assignments</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('schedule_assignor_2019')}">Assignor's View of Assignments</a></li>
      <li><a href="{$this->generateUrl('game_official_summary')}">Export Detailed Referee Summary</a></li>
      <li><a href="{$this->generateUrl('schedule_official_2019',['_format' => 'xls', '_default' => true])}">Export 
      Simple Schedule with Referees</a></li>
      <hr>
      <li><a href="{$this->generateUrl('detailed_instruction')}" target="_blank">Referee Self-Assignment Instruction</a></li>
EOT;

        $html .= <<<EOT
    </ul>
  </div>
</div>
EOT;

    } else {
      $html = "";
    }

      return $html;      
    }

    protected function renderAccountManagement()
    {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Account Management</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('project_person_admin_listing')}">Manage Registered People</a></li>
      <li><a href="{$this->generateUrl('project_person_admin_listing',['_format' => 'xls'])}">Export Registered People</a></li>
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderCommunications()
    {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Communications</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('app_text_alerts')}">RainedOut Messaging</a></li>
      <li><a href="https://www.rainedout.net/admin/login.php?a=0588afab19ee214eca29" target="_blank">RainedOut Admin Login</a></li>
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderAdminHelp()
    {
      return <<<EOT
<legend>Need help?</legend>
<div class="app_help">
  <ul class="cerad-common-help">
    <ul class="ul_bullets">
      <li>For help with Referee Assignments, contact {$this->project['administrator']['name']} at <a href="mailto:{$this->project['administrator']['email']}">{$this->project['administrator']['email']}</a> or at {$this->project['administrator']['phone']}</li>
      <li>For help with Schedule Management, contact {$this->project['schedules']['name']} at <a 
      href="mailto:{$this->project['schedules']['email']}">{$this->project['schedules']['email']}</a> or at {$this->project['schedules']['phone']}</li>
      <li>For help with Account Management,  contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
    </ul>
  </ul>
</div>
EOT;
    }
}
