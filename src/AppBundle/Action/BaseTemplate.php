<?php
namespace AppBundle\Action;

class BaseTemplate extends AbstractTemplate
{
    protected $title = 'NG2016';
    protected $content = null;

    public function setContent($content)
    {
        $this->content = $content;
    }
    public function render()
    {
        return <<<EOT
        <!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$this->escape($this->project['abbv'])}</title>
            <link rel="icon" type="image/x-icon" href="/favicon.ico" />
            <link rel="stylesheet" type="text/css" href="/css/normalize.css" media="all" />
            <link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css" media="all" />
            <link rel="stylesheet" type="text/css" href="/css/zayso.css" media="all" />

          </head>
          <body>
            <div id="layout-body">
              <div id="layout-header" style="width: 100%; text-align: center;">
                <h1>{$this->escape($this->project['title'])}</h1>
              </div>
              <div id="layout-topmenu">
        {$this->renderTopMenu()}
              </div>
              <div id="layout-content">
        {$this->content}
              </div>
            </div>
        {$this->renderScripts()}

        <!-- Bootstrap core JavaScript
        ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="/js/jquery.min.js"><\/script>')</script>
        <script src="/js/bootstrap.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="/js/ie10-viewport-bug-workaround.js"></script>
        <script src="/js/zayso.js"></script>

          </body>
        </html>
EOT;
    }
    /* ====================================================
     * Top Menu for Guest
     */
    protected function renderTopMenuForGuest()
    {
        return <<<EOD
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
              {$this->renderTopMenuSchedules()}
              {$this->renderTopMenuResults()}
            </ul>
            {$this->renderSignIn()}
        </div><!-- /.navbar-collapse -->
      </div><!-- /.container-fluid -->
    </nav>
EOD;
    }

/*  Bootstrap menu  */
  protected function renderTopMenu()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->renderTopMenuForGuest();
        }
        
        return <<<EOT
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
              {$this->renderTopMenuSchedules()}
              {$this->renderTopMenuResults()}
              {$this->renderRefereeSchedules()}
            </ul>
            <ul class="nav navbar-nav navbar-right">
            {$this->renderMyAccount()}
            {$this->renderAdmin()}
            {$this->renderSignOut()}
            </ul>
        </div><!-- /.navbar-collapse -->
      </div><!-- /.container-fluid -->
    </nav>
EOT;
    }
    
    protected function renderTopMenuSchedules()
    {
        return <<<EOT
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Schedules <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="{$this->generateUrl('app_schedule_team')}">Team Schedules</a></li>
              <li><a href="{$this->generateUrl('app_schedule_game')}">Game Schedules</a></li>
            </ul>
          </li>
EOT;
    }
    protected function renderTopMenuResults()
    {
        return <<<EOT
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Results <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li>
                <a href="{$this->generateUrl('app_results_poolplay')}">Pool Play</a>
              </li>
              <li>
                <a href="{$this->generateUrl('app_results_medalround')}">Medal Round</a>
              </li>
              <li>
                <a href="{$this->generateUrl('app_results_sportsmanship')}">Sportsmanship</a>
              </li>
              <li>
                <a href="{$this->generateUrl('app_results_final')}">Final Standings</a>
              </li>
            </ul>
          </li>
EOT;
    }
    protected function renderSignIn()
    {
        return <<<EOT
          <ul class="nav navbar-nav navbar-right">
            <li>
              <a href="{$this->generateUrl('app_welcome')}">Sign In</a>
            </li>
          </ul>
EOT;
    }
    protected function renderSignOut()
    {
        return <<<EOT
            <li>
              <a href="{$this->generateUrl('cerad_user_logout')}">Sign Out</a>
            </li>
EOT;
    }
    
    protected function renderRefereeSchedules()
    {
      return <<<EOT
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Referee Schedules <span class="caret"></span></a>
         <ul class="dropdown-menu">
           <li>
             <a href="/project/natgames/schedule-user">My Schedule</a>
           </li>
           <li>
             <a href="/project/natgames/schedule-referee">Request Assignments</a>
           </li>
                 <li class="last">
             <a href="/project/natgames/schedule-assignor">Assignor Schedule</a>
           </li>
         </ul>
       </li>
EOT;
    }

    protected function renderMyAccount()
    {
      return <<<EOT
        <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">My Account <span class="caret"></span></a>
        <ul class="dropdown-menu">
          <li>
            <a href="{$this->generateUrl('app_home')}">My Account</a>
          </li>
          <li>
            <a href="{$this->generateUrl('app_home')}">My Plans</a>
          </li>
          <li>
            <a href="{$this->generateUrl('app_home')}">My Info</a>
          </li>
          <li>
            <a href="/project/natgames/schedule-user">My Schedule</a>
          </li>
        </ul>
EOT;
    }
    
    protected function renderAdmin()
    {
      return <<<EOT
        <li>
          <a href="/admin">Admin</a>
        </li>
EOT;
    }
    /* ====================================================
     * Maybe implement blocks later
     */
    protected $scripts = [];

    protected function renderStylesheets()
    {
        return null;
    }
    protected function renderScripts()
    {
        $html = null;
        foreach($this->scripts as $script) {
            $html .= $script . "\n";
        }
        return $html;
    }
    public function addStylesheet() {}

    public function addScript($script)
    {
        $this->scripts[] = $script;
    }
}