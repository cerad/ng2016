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
    <script src="/js/jquery.min.js"></script>
    <script src="/js/zayso.js"></script>
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
  </body>
</html>
EOT;
    }
    /* ====================================================
     * Top Menu for Guest
     */
    protected function renderTopMenu()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->renderTopMenuForGuest();
        }
        return <<<EOD
<div class='cssmenu'>
<ul>
{$this->renderTopMenuSchedules()}
{$this->renderTopMenuResults()}
  <li class="has-sub">Referee Schedules
    <ul>
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
  <li class="right">
    <a href="{$this->generateUrl('cerad_user_logout')}">Logout</a>
  </li>
  <li class="has-sub right">My Account
  <ul>
    <li>
      <a href="/person/update">My Account</a>
    </li>
    <li>
      <a href="/person-plan/update">My Plans</a>
    </li>
    <li>
      <a href="/home">My Info</a>
    </li>
    <li class="last">
      <a href="/project/natgames/schedule-user">My Schedule</a>
    </li>
  </ul>
  <li class="right">
    <a href="/admin">Admin</a>
  </li>
</ul>
</div>
EOD;
    }
    protected function renderTopMenuForGuest()
    {
        return <<<EOD
<div class='cssmenu'>
<ul>
{$this->renderTopMenuSchedules()}
{$this->renderTopMenuResults()}
  <li class="right">
    <a href="{$this->generateUrl('app_welcome')}">Login</a>
  </li>
</ul>
</div>
EOD;
    }
    protected function renderTopMenuSchedules()
    {
        return <<<EOD
<li class="has-sub">Schedules
  <ul>
    <li>
      <a href="{$this->generateUrl('app_schedule_team')}">Team Schedules</a>
    </li>
    <li class="last">
      <a href="{$this->generateUrl('app_schedule_game')}">Game Schedules</a>
    </li>
  </ul>
</li>
EOD;
    }
    protected function renderTopMenuResults()
    {
        return <<<EOD
<li class="has-sub">Results
  <ul>
    <li>
      <a href="/project/natgames/results-poolplay">Pool Play</a>
    </li>
    <li>
      <a href="/project/natgames/results-playoffs">Medal Round</a>
    </li>
    <li>
      <a href="/project/natgames/results-sportsmanship">Sportsmanship</a>
    </li>
    <li class="last">
      <a href="/project/natgames/results-sportsmanship">Final Standings</a>
    </li>
  </ul>
</li>
EOD;
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