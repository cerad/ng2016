<?php

namespace AppBundle\Action\App\Admin;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;

class AdminView extends AbstractView2
{
    private $project;
    private $projectId;

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

      <li><a href="{$this->generateUrl('app_results_poolplay')}">Pool Play</a></li>

      <li><a href="{$this->generateUrl('app_results_medalround')}">Medal Round</a></li>

      <li><a href="{$this->generateUrl('app_results_sportsmanship')}">Sportsmanship</a></li>

      <li><a href="{$this->generateUrl('app_results_final')}">Final Standings</a></li>
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
      <li><a href="{$this->generateUrl('app_schedule_game')}">View Game Schedule</a></li>
      <li><a href="{$this->generateUrl('app_schedule_team')}">View Team Schedule</a></li>
      <li><a href="{$this->generateUrl('app_schedule_game',['_format' => 'xls'])}">Export Game Schedule (Excel)</a></li>
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
      <li><a href="#">View Teams</a></li>
      <li><a href="#">Export Teams (Excel)</a></li>
EOT;
      if ($this->isGranted('ROLE_ADMIN')) {
        $html .= <<<EOT
      <li><a href="#">Import/Update/Link Teams (Excel)</a></li>
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
      <li><a href="{$this->generateUrl('schedule_official_2016')}">View Referee Assignments</a></li>
      <li><a href="#">Export Referee Assignments (Excel)</a></li>
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
