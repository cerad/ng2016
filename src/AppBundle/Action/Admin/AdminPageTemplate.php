<?php

namespace AppBundle\Action\Admin;

use AppBundle\Action\PageTemplate;

class AdminPageTemplate extends PageTemplate
{
    /* Admin Page content */
    public function render($params = [])
    {
        $content =
<<<EOT
<h3>Administrative Functions</h3>
EOT;
        $content .= $this->renderMatchReporting();
  
        $content .= $this->renderScheduleManagement();
  
        $content .= $this->renderTeamManagement();
  
        $content .= $this->renderRefereeManagement();
  
        $content .= $this->renderAccountManagement();
          
        $content .=  
<<<EOT
<div class="panel-float-clear"></div>
EOT;

        $content .= $this->renderAdminHelp();
      
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
      <li><a href="#">Enter Match Results</a></li>

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
      <li><a href="#">View Game Schedule</a></li>
      <li><a href="#">View Team Schedule</a></li>
      <li><a href="#">Export Core Game Schedule (Excel)</a></li>
      <li><a href="#">Export Extra Game Schedule (Excel)</a></li>
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
    <div class="app_help">
    <br/>
    <hr>
    <h3>Need help?</h3>
    <ul class="cerad-common-help">
      <ul class="ul_bullets">
        <li>For help with Match Reporting, contact Art Hundiak at <a href="mailto:ahundiak@gmail.com">ahundiak@gmail.com</a> or at 256-457-5943</li>
        <li>For help with Schedule Management, contact Bill Owen at <a href="mailto:stats@ayso13.org">stats@ayso13.org</a> or at 626-484-5439</li>
        <li>For help with Referee Assignments, contact Jody Kinsey at <a href="mailto:jodykinsey23@gmail.com">jodykinsey23@gmail.com</a> or at 909-262-8806</li>
        <li>For help with Account Management, contact Art Hundiak at <a href="mailto:ahundiak@gmail.com">ahundiak@gmail.com</a> or at 256-457-5943</li>
      </ul>
    </ul>
    </div>
EOT;
    }
}
