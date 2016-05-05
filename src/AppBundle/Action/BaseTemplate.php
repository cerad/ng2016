<?php
namespace AppBundle\Action;

class BaseTemplate extends AbstractTemplate
{
    protected $title = 'NG2016';
    protected $content = null;

    private $showHeaderImage;
    private $showResultsMenu;
    
    public function __construct($showHeaderImage,$showResultsMenu)
    {
        $this->showHeaderImage = $showHeaderImage;
        $this->showResultsMenu = $showResultsMenu;
    }
    public function setContent($content)
    {
        $this->content = $content;
    }
    public function render()
    {
      return <<<EOT
        {$this->renderHead()}
        {$this->renderHeader()}
        <body>
            <div id="layout-topmenu">
              {$this->renderTopMenu()}
            </div>
            <div class="container">
              {$this->content}
            </div>
      {$this->renderScripts()}

      {$this->renderFooter()}

EOT;
    }
    
    /*  DOC & Header  */
    protected function renderHead()
    {
    return <<<EOT
        <!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$this->escape($this->project['abbv'])}</title>
<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="/images/favicon.ico">
<link rel="apple-touch-icon" type="image/png" href="/images/apple-touch-icon-72x72.png"><!-- iPad -->
<link rel="apple-touch-icon" type="image/png" sizes="114x114" href="/images/apple-touch-icon-114x114.png"><!-- iPhone4 -->
<link rel="icon" type="image/png" href="/images/apple-touch-icon-114x114.png"><!-- Opera Speed Dial, at least 144?114 px -->
            <link rel="stylesheet" type="text/css" href="/css/normalize.css" media="all" />
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
          <link rel="stylesheet" href="http://dbtek.github.io/bootstrap-vertical-tabs/assets/bower_components/bootstrap-vertical-tabs/bootstrap.vertical-tabs.css" type="text/css">
            <link rel="stylesheet" type="text/css" href="/css/zayso.css" media="all" />
          </head>
EOT;
    }
    
    protected function renderHeader()
    {
      if (!$this->showHeaderImage) {
        $html = 
<<<EOT
    <div id="banner">
          <h1>
          <a href="http://www.aysonationalgames.org/" target="_blank"><img src="/images/National_Games.png" height="30" alt="National Games"></a>
          {$this->escape($this->project['title'])}
          </h1>
    </div>
EOT;
      } else {
          $html =
<<<EOT
    <div class="skBanners">
        <a href="http://www.aysonationalgames.org/" target="_blank"><img class="width-90" src="/images/header-ipad_01.png"></a>
        <center class="skFont  width-90">AYSO WELCOMES YOU TO PALM BEACH COUNTY, FLORIDA, JULY 5-10, 2016</center>
    </div>
EOT;
      }
  
      return $html;
  
    }
    
    /* Footer item go here */
    protected function renderFooter()
    {
        return
<<<EOT
    <div class="cerad-footer">
      <br />
      <hr>
      <p> zAYSO - For assistance contact {$this->project['support']['name']} at
      <a href="mailto:{$this->project['support']['email']}?subject={$this->project['support']['subject']}">{$this->project['support']['email']}</a>
      or {$this->project['support']['phone']} </p>
    </div>
    
				<div class="clear-both"></div>
			</div>
        </body>
        </html>
EOT;
    }
    
/*  Bootstrap menu  */
  protected function renderTopMenu()
    {
        $html =
<<<EOT
        <nav class="navbar navbar-default">
          
          <div class="container">
             <div class="navbar-header">
                 <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#topmenu">
                     <span class="sr-only">Toggle navigation</span>
                     <span class="icon-bar"></span>
                     <span class="icon-bar"></span>
                     <span class="icon-bar"></span>
                 </button>
             </div>
            
             <!-- Collect the nav links, forms, and other content for toggling -->
             <div id="topmenu" class="collapse navbar-collapse">
EOT;
        $html .= $this->renderMenuForGuest();
        
        $html .= $this->renderMenuForUser();
                
        $html .=
<<<EOT
            </div><!-- /.navbar-collapse -->
          </div><!-- /.container-->

        </nav>
EOT;
      return $html;
    }
    
    /* ====================================================
     * Top Menu for Guest
     */
    protected function renderMenuForGuest()
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $html = <<<EOT
            <ul class="nav navbar-nav">
               {$this->renderHome()}
              {$this->renderTopMenuSchedules()}
              {$this->renderTopMenuResults()}
            </ul>
EOT;
        } else {
            $html = <<<EOT
            <ul class="nav navbar-nav">
                {$this->renderWelcome()}
               {$this->renderTopMenuSchedules()}
               {$this->renderTopMenuResults()}
             </ul>
EOT;
        }
        
        return $html;
    }
    
    /* ====================================================
     * Top Menu for Users
     */
    protected function renderMenuForUser()
    {
      if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
        $html =
<<<EOT
         <ul class="nav navbar-nav navbar-right">
           {$this->renderRefereeSchedules()}
           {$this->renderMyAccount()}
EOT;
          if ( $this->isGranted('ROLE_STAFF') ) {
              $html .= $this->renderAdmin();
          }
          $html .= $this->renderSignOut();
$html .= <<<EOT
        </ul>
EOT;
      } else { // TODO Do not use _SERVER
          /*
        if (strpos($_SERVER['REQUEST_URI'], 'welcome')) {
            $html = $this->renderCreateNewAccount();            
        } else {
            $html = $this->renderSignIn();
        }*/
          $html = '';  //$this->renderSignIn();
      }
      return $html;
    }
    
    protected function renderTopMenuSchedules()
    {
        if (!$this->showResultsMenu) {
            return null;
        }
        return
<<<EOT
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">SCHEDULES <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="{$this->generateUrl('schedule_game_2016'    )}">GAME    SCHEDULES</a></li>
            <li><a href="{$this->generateUrl('schedule_team_2016'    )}">TEAM    SCHEDULES</a></li>
            <li><a href="{$this->generateUrl('schedule_official_2016')}">REFEREE SCHEDULES</a></li>
          </ul>
        </li>
EOT;
    }
    
    protected function renderTopMenuResults()
    {
        if (!$this->showResultsMenu) {
            return null;
        }
        return
<<<EOT
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">RESULTS <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="{$this->generateUrl('app_results_poolplay')}">POOL PLAY</a></li>
            <li><a href="{$this->generateUrl('app_results_medalround')}">MEDAL ROUND</a></li>
            <li><a href="{$this->generateUrl('app_results_sportsmanship')}">SPORTSMANSHIP</a></li>
            <li><a href="{$this->generateUrl('app_results_final')}">FINAL STANDINGS</a></li>
          </ul>
        </li>
EOT;
    }
    
    protected function renderCreateNewAccount()
    {
        return
<<<EOT
          <ul class="nav navbar-nav navbar-right">
            <li><a href="{$this->generateUrl('user_create')}">CREATE NEW ACCOUNT</a></li>
          </ul>
EOT;
    }

    protected function renderSignIn()
    {
        return
<<<EOT
          <ul class="nav navbar-nav navbar-right">
            <li><a href="{$this->generateUrl('user_login')}">SIGN IN</a></li>
          </ul>
EOT;
    }
    
    protected function renderSignOut()
    {
        return
<<<EOT
            <li><a href="{$this->generateUrl('user_logout')}">SIGN OUT</a></li>
EOT;
    }
    
    protected function renderRefereeSchedules()
    {
        if (!$this->showResultsMenu) {
            return null;
        }
        return
<<<EOT
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">REFEREES <span class="caret"></span></a>
         <ul class="dropdown-menu">
            <li><a href="/project/natgames/schedule-user">MY SCHEDULE</a></li>
            <li><a href="/project/natgames/schedule-referee">REQUEST ASSIGNMENTS</a></li>
            <li><a href="/project/natgames/schedule-assignor">ASSIGNOR SCHEDULE</a></li>
         </ul>
       </li>
EOT;
    }

    protected function renderMyAccount()
    {
        if (!$this->showResultsMenu) {
            return null;
        }
        return
<<<EOT
        <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">MY STUFF<span class="caret"></span></a>
        <ul class="dropdown-menu">
          <li><a href="{$this->generateUrl('project_person_update')}">MY PLANS</a></li>
          <li><a href="{$this->generateUrl('app_home')}">MY AYSO INFO</a></li>
          <li><a href="{$this->generateUrl('app_home')}">MY SCHEDULE</a></li>
        </ul>
EOT;
    }
    
    protected function renderHome()
    {
        //if (!$this->showResultsMenu) {
        //    return null;
        //}
        return
<<<EOT
        <li>
          <a href="{$this->generateUrl('app_home')}">HOME</a>
        </li>
EOT;
    }
    protected function renderWelcome()
    {
        //if (!$this->showResultsMenu) {
        //    return null;
        //}
        return
<<<EOT
        <li>
          <a href="{$this->generateUrl('app_welcome')}">WELCOME</a>
        </li>
EOT;
    }    protected function renderAdmin()
    {
        return <<<EOT
<li>
  <a href="{$this->generateUrl('app_admin')}">ADMIN</a>
</li>
EOT;
    }
    protected function renderScripts()
    {
        return
<<<EOT
          <!-- Bootstrap core JavaScript -->
          <!-- ================================================== -->
          <!-- Placed at the end of the document so the pages load faster -->
          <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
          <script>window.jQuery || document.write('<script src="/js/jquery.min.js"><\/script>')</script>
          <!-- Latest compiled and minified JavaScript -->
          <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
          <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
          <script src="/js/ie10-viewport-bug-workaround.js"></script>
          <script src="/js/zayso.js"></script>
EOT;
    }
    
    /* ====================================================
     * Maybe implement blocks later
     */
    protected function renderStylesheets()
    {
        return null;
    }

    public function addStylesheet() {}

    public function addScript($script)
    {
        $this->scripts[] = $script;
    }

    protected function renderSponsors()
    {
        return <<<EOT
<div class="skSponsor">
  <h2 class="skHead">Our Sponsors</h2>

  <div id="dnn_sWebThemeSponsors">
    <div class="caroufredsel_wrapper" style="display: block; text-align: left; float: none; position: relative; top: auto; right: auto; bottom: auto; left: auto; z-index: auto; width: 873px; height: 90px; margin: auto; overflow: hidden;">
      <ul class="sponsors" style="text-align: left; float: none; position: absolute; top: 0px; right: auto; bottom: auto; left: 37px; margin: 0px; width: 6073px; height: 90px; z-index: auto;">
        <li class="item last" style="margin-right: 20px;"><a class="link" href="http://www.advantage.com"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635919960945465587.png" alt="" height="90" width="160"></a></li>

        <li class="item first" style="margin-right: 20px;"><a class="link" href="http://msn.foxsports.com/foxsoccer/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635839855409153276.png" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 20px;"><a class="link" href="http://kerrygoldusa.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635839856947279111.png" alt="" height="90" width="160"></a></li>

        <li class="item" style="margin-right: 56px;"><a class="link" href="http://www.dickssportinggoods.com" target="_blank"><img src="/DesktopModules/BSB/BSB.Content/ImageViewer.ashx?w=160&amp;h=90&amp;portalid=14066" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 20px;"><a class="link" href="http://www.shutterfly.com/?pid=AYSO&amp;psid=WEB&amp;cid=AYSOHOMEPGE" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635878717148972822.png" alt="" height="90" width="160"></a></li>

        <li class="item" style="margin-right: 20px;"><a class="link" href="http://www.scoresports.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635878715462721952.png" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 20px;"><a class="link" href="http://www.dole.com/fruitsquishems" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635839858411967356.png" alt="" height="90" width="160"></a></li>

        <li class="item" style="margin-right: 20px;"><a class="link" href="https://www.nesquik.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635839858717123723.png" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 20px;"><a class="link" href="http://www.continentaltire.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635878718382254693.png" alt="" height="90" width="160"></a></li>

        <li class="item" style="margin-right: 20px;"><a class="link" href="https://www.worldsfinestchocolate.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635839859603530423.png" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 20px;"><a class="link" href="http://avocadosfrommexico.com/" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635878715204284317.png" alt="" height="90" width="160"></a></li>

        <li class="item" style="margin-right: 20px;"><a class="link" href="http://www.palmbeachsports.com" target="_blank"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635854381461995679.png" alt="" height="90" width="160"></a></li>

        <li class="item alt" style="margin-right: 57px;"><a class="link" href="http://www.pbmisters.com"><img src="https://bsbproduction.s3.amazonaws.com/portals/14066/homesponsors/homesponsors635908300797160859.png" alt="" height="90" width="160"></a></li>
      </ul>
    </div><a class="prevSponsor" href="javascript:void(0)" style="display: block;">&lt;</a> <a class="nextSponsor" href="javascript:void(0)" style="display: block;">&gt;</a>
  </div><script type="text/javascript">
                      jQuery(document).ready( function() {
                                jQuery("#dnn_sWebThemeSponsors ul.sponsors").show().carouFredSel({
                                        prev: "#dnn_sWebThemeSponsors .prevSponsor", 
                                        next: "#dnn_sWebThemeSponsors .nextSponsor", 
                                        auto: true, 
                                        width: "100%",
                                        circular: true, 
                                        items: { visible: 0 },
                                        scroll: { duration: 1000, pauseOnHover: true }
                                }).parent().css("margin", "auto");
                        });
  </script>

  <div class="clear-both"></div>
</div>

EOT;
    }


}