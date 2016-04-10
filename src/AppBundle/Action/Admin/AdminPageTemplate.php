<?php

namespace AppBundle\Action\Admin;

use AppBundle\Action\PageTemplate;

class AdminPageTemplate extends PageTemplate
{
    protected $project = null;
    
    
    /* Admin Page content */
    public function render($params = [])
    {
        $this->project = $params['project'];
    
        $content =
<<<EOT
<div class="container no-disc">
<h3>Administrative Functions</h3>
EOT;
        $content .= $this->renderMatchReporting();
  
        $content .= $this->renderScheduleManagement();
  
        $content .= $this->renderRefereeManagement();
  
        $content .= $this->renderTeamManagement();
  
        $content .= $this->renderAccountManagement();
          
        $content .=  
<<<EOT
<div class="panel-float-clear"></div>
EOT;

        $content .= $this->renderAdminHelp();
<<<EOT
</div>  <!-- .container -->
EOT;

        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
    
    /* Match Reporting content */
    protected function renderMatchReporting()
    {
      if ($this->isGranted('ROLE_SCORE_ENTRY')) {
        $html =
<<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Match Reporting</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('game_report_update',['gameNumber' => 11001])}">Enter Match Results</a></li>

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
        $html =
<<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Schedule Management</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="{$this->generateUrl('app_schedule_game')}">View Game Schedule</a></li>
      <li><a href="{$this->generateUrl('app_schedule_team')}">View Team Schedule</a></li>
      <li><a href="{$this->generateUrl('app_schedule_game',['_format' => 'core'])}">Export Core Game Schedule (Excel)</a></li>
      <li><a href="{$this->generateUrl('app_schedule_game',['_format' => 'extra'])}">Export Extra Game Schedule (Excel)</a></li>
EOT;
      if ($this->isGranted('ROLE_SUPER_ADMIN')) {
        $html .=
<<<EOT
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
        $html =
<<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Team Management</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="#">View Teams</a></li>
      <li><a href="#">Export Teams (Excel)</a></li>
EOT;
      if ($this->isGranted('ROLE_SUPER_ADMIN')) {
        $html .=
<<<EOT
      <li><a href="#">Import/Update/Link Teams (Excel)</a></li>
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

    protected function renderRefereeManagement()
    {
      if ($this->isGranted('ROLE_ASSIGNOR')) {
        $html =
<<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Referee Assignments</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="#">View Referee Assignments</a></li>
      <li><a href="#">Export Core Referee Assignments (Excel)</a></li>
      <li><a href="#">Export Extra Referee Assignments (Excel)</a></li>
EOT;

      if ($this->isGranted('ROLE_SUPER_ADMIN')) {
        $html .=
<<<EOT
      <li><a href="#">Import Referee Assignments (Excel)</a></li>
      <li><a href="#">View Unregistered Referee List</a></li>
EOT;
      }
    } else {
      $html = "";
    }

        $html .=
<<<EOT
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderAccountManagement()
    {
        $html =
<<<EOT
<div class="panel panel-default panel-float-left">
  <div class="panel-heading">
    <h1>Account Management</h1>
  </div>
  <div class="panel-body">
    <ul>
      <li><a href="#">View Registered People</a></li>
      <li><a href="#">Export Registered People (Excel)</a></li>
EOT;
      
      if ($this->isGranted('ROLE_ADMIN')) {
        $html .=
<<<EOT
      <li><a href="#">View Unverified Registered People</a></li>
      <li><a href="#">Export Unverified Registered People (Excel)</a></li>
EOT;
      }
      
      if ($this->isGranted('ROLE_SUPER_ADMIN')) {
        $html .=
<<<EOT
      <li><a href="#">Sync eAYSO Information</a></li>
      <li><a href="#">Import AYSO Information</a></li>
EOT;
      }
      
      $html .=
<<<EOT
      <li><a href="#">View Staff Roles</a></li>
EOT;
    
        $html .= 
<<<EOT
    </ul>
  </div>
</div>
EOT;

      return $html;      
    }

    protected function renderAdminHelp()
    {
      return
<<<EOT
    <legend>Need help?</legend>
    <div class="app_help">
    <ul class="cerad-common-help">
      <ul class="ul_bullets">
        <li>For help, contact {$this->project['administrator']['name']} at <a href="mailto:{$this->project['administrator']['email']}">{$this->project['administrator']['email']}</a> or at {$this->project['administrator']['phone']}</li>
        <li>For help with Referee Assignments, contact {$this->project['assignor']['name']} at <a href="mailto:{$this->project['assignor']['email']}">{$this->project['assignor']['email']}</a> or at {$this->project['assignor']['phone']}</li>
        <li>For help with Account Management, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
        <li>For help with Schedule Management, contact {$this->project['schedules']['name']} at <a href="mailto:{$this->project['schedules']['email']}">{$this->project['schedules']['email']}</a> or at {$this->project['schedules']['phone']}</li>
      </ul>
    </ul>
    </div>
EOT;
    }
}
