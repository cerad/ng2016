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
          
        $content .=  <<<EOT
</div> <!-- class="container no-disc" -->
<div class="panel-float-clear"></div>
EOT;
        $content .= $this->switchUserForm->render();
        $content .= $this->renderAdminHelp();

        return $this->renderBaseTemplate($content);
    }
    
    /* Match Reporting content */
    protected function renderMatchReporting()
    {
      if ($this->isGranted('ROLE_SCORE_ENTRY')) {
        $html = <<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Match Reporting</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('game_report_update',['projectId' => $this->projectId,'gameNumber' => 11001])}">Enter Match Results</a></li>

      <li><a href="{$this->generateUrl('results_poolplay_2016')}">Pool Play</a></li>

      <li><a href="{$this->generateUrl('results_medalround_2016')}">Medal Round</a></li>

      <li><a href="{$this->generateUrl('results_sportsmanship_2016')}">Sportsmanship</a></li>

      <li><a href="{$this->generateUrl('results_final_2016')}">Final Standings</a></li>
    </ul>
  </div>
</div>

EOT;
      } else {
        $html = "";
      }
      
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
        <li><a href="{$this->generateUrl('schedule_game_2016',['_format' => 'xls'])}">Export Game Schedule (Excel)</a></li>
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_qf'])}">Export Quarter-Finals Schedule for review</a></li>
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_sf'])}">Export Semi-Finals Schedule for review</a></li>
        <li><a href = "{$this->generateUrl('schedule_medalroundcalc_2016',['_format' => 'xls_fm'])}">Export Finals Schedule for review</a></li>
EOT;
      if ($this->isGranted('ROLE_ADMIN')) {
        $html .= <<<EOT
     <li><a href="#">Import Game Schedule (Excel)</a></li> 
EOT;
      }
      
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
      <li><a href="{$this->generateUrl('regteam_export')}">Export Teams (Excel)</a></li>
EOT;
      if ($this->isGranted('ROLE_ADMIN')) {
        $html .= <<<EOT
      <li><a href="{$this->generateUrl('regteam_import')}">Import/Update Teams (Excel)</a></li>
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
      <li><a href="{$this->generateUrl('schedule_official_2016',['_format' => 'xls'])}">Export Referee Assignment Requests (Excel)</a></li>
      <li><a href="{$this->generateUrl('schedule_assignor_2016')}">View Assignor Assignments</a></li>
EOT;

      if ($this->isGranted('ROLE_ADMIN')) {
        $html .= <<<EOT
      <li><a href="#">Import Referee Assignments (Excel)</a></li>
EOT;
      }

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
      <li><a href="{$this->generateUrl('project_person_admin_listing')}">Mangage Registered People</a></li>
      <li><a href="{$this->generateUrl('project_person_admin_listing',['_format' => 'xls'])}">Export Registered People (Excel)</a></li>
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
      <li>For help with Account Management,  contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
      <li>For help with Schedule Management, contact {$this->project['schedules']['name']} at <a href="mailto:{$this->project['schedules']['email']}">{$this->project['schedules']['email']}</a></li>
    </ul>
  </ul>
</div>
EOT;
    }
}
