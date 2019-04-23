<?php declare(strict_types=1);

namespace Zayso\Main\Admin;

use Zayso\Common\Traits\AuthorizationTrait;
use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\ProjectInterface;

class AdminTemplate
{
    use EscapeTrait;
    use RouterTrait;
    use AuthorizationTrait;

    private $project;
    private $projectId;
    private $switchUserForm;
    
    public function __construct(
        AdminSwitchUserForm $switchUserForm
    ) {
        $this->switchUserForm = $switchUserForm;
    }

    /* Admin Page content */
    public function render(ProjectInterface $project)
    {
        $content = <<<EOT
<div class="container no-disc">
<h3>Administrative Functions</h3>
EOT;
        $content .= $this->renderMatchReporting($project);
  
        $content .= $this->renderScheduleManagement();
  
        $content .= $this->renderRefereeManagement();
  
        $content .= $this->renderTeamManagement();
  
        $content .= $this->renderAccountManagement();
        
        $content .= $this->renderCommunications($project);
          
        $content .=  <<<EOT
</div> <!-- class="container no-disc" -->
<br>
<div class="panel-float-clear"></div>
EOT;
        $content .= $this->switchUserForm->render();
        $content .= $this->renderAdminHelp($project);

        return $content;
    }
    
    /* Match Reporting content */
    protected function renderMatchReporting(ProjectInterface $project)
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
      <li><a href="{$this->generateUrl('game_report_update',['projectId' => $project->projectId,'gameNumber' => 11001])}">Enter Match Results</a></li>
EOT;
      }
      
        $html .= <<<EOT
      <li><a href="{$this->generateUrl('results_poolplay_2016')}">Pool Play</a></li>

      <li><a href="{$this->generateUrl('results_medalround_2016')}">Medal Round</a></li>

      <li><a href="{$this->generateUrl('results_sportsmanship_2016')}">Sportsmanship</a></li>

      <li><a href="{$this->generateUrl('results_final_2016')}">Final Standings</a></li>
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
        <li><a href="{$this->generateUrl('schedule_game_2016')}">View Game Schedule</a></li>
        <li><a href="{$this->generateUrl('schedule_team_2016')}">View Team Schedule</a></li>
        <li><a href="{$this->generateUrl('schedule_game_2016',['_format' => 'xls'])}">Export Game Schedule</a></li>
        <li><a href="{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_qf'])}">Export Quarter-Finals Schedule for review</a></li>
        <li><a href="{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_sf'])}">Export Semi-Finals Schedule for review</a></li>
        <li><a href="{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_fm'])}">Export Finals Schedule for review</a></li>
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
      <li><a href="{$this->generateUrl('schedule_official_2016')}">View Referee Assignment Requests</a></li>
      <li><a href="{$this->generateUrl('schedule_official_2016',['_format' => 'xls'])}">Export Referee Assignment Requests</a></li>
      <li><a href="{$this->generateUrl('schedule_assignor_2016')}">View Assignor Assignments</a></li>
      <li><a href="{$this->generateUrl('game_official_summary')}">Export Referee Summary</a></li>
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
      <li><a href="{$this->generateUrl('reg_person_admin_listing')}">Mangage Registered People</a></li>
      <li><a href="{$this->generateUrl('reg_person_admin_listing',['_format' => 'xls'])}">Export Registered People</a></li>
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderCommunications(ProjectInterface $project)
    {
        $rainedOutKey = $project->rainedOutKey;

        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Communications</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('app_text_alerts')}">RainedOut Messaging</a></li>
      <li><a href="https://www.rainedout.net/admin/login.php?a={$rainedOutKey}" target="_blank">RainedOut Admin Login</a></li>
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderAdminHelp(ProjectInterface $project)
    {
        $support   = $project->support;
        $assignor  = $project->refAssignor;
        $scheduler = $project->gameScheduler;

      return <<<EOT
<legend>Need help?</legend>
<div class="app_help">
  <ul class="cerad-common-help">
    <ul class="ul_bullets">
      <li>For help with Referee Assignments, contact {$assignor->name} at 
        <a href="mailto:{$assignor->email}">{$assignor->email}</a> or at {$assignor->phone}
      </li>
      <li>For help with Schedule Management, contact {$scheduler->name} at 
        <a href="mailto:{$scheduler->email}">{$scheduler->email}</a> or at {$scheduler->phone}
      </li>
      <li>For help with Account Management,  contact {$support->name} at 
        <a href="mailto:{$support->email}">{$support->email}</a> or at {$support->phone}
      </li>
    </ul>
  </ul>
</div>
EOT;
    }
}
