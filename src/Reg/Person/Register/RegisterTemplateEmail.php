<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Register;

use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\CurrentProjectTrait;
use Zayso\Reg\Person\RegPerson;
use Zayso\Reg\Person\RegPersonViewDecorator;

class RegisterTemplateEmail
{
    use RouterTrait;
    use EscapeTrait;
    use CurrentProjectTrait;

    private $personView;

    /* define inline styling for gmail */
    protected $styleSkHeader = '
        position: relative;
    ';
    protected $styleSkFontEmail = '
        font-size: 14px;
    ';
    protected $tableClass = '
        border-spacing: 0;
        border-collapse: collapse;
        background-color: transparent;
        font-size: inherit;
        table-layout: fixed;
        margin-left: auto;
        margin-right: auto;
        width: 600px;
        border: 2px solid black;
        margin-bottom: 0px;
    ';
    protected $styleBodyEmail = 'width: inherit;';
    protected $stylePEmail = '
        font-size: 14px;
        text-align: center;
    ';
    protected $styleP = '
        margin-bottom: 0.5em;
    ';
    protected $styleClearBoth = '
        clear: both
    ';
    protected $stylePStrong = '
        text-decoration: underline;
        font-size: 14px;
        font-weight: bold;
    ';
    protected $styleTd = '
        height: 2em;
        text-align: center;
        vertical-align: middle;
    ';

    public function __construct(RegPersonViewDecorator $personView)
    {
        $this->personView = $personView;
    }

    public function renderHtml(RegPerson $person)
    {
        $personView = $this->personView;
        $personView->setPerson($person);
        $refAdmin = $this->currentProject->refAdmin;

        return <<<EOD
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=us-ascii">
  <title=""/>
</head>
<body style="{$this->styleBodyEmail}" >
  <center>
    <div style="{$this->styleSkHeader}">
      <h1>
          <img src="https://ng2019.zayso.org/images/header-ipad_01.png" width="90%" alt="">
      </h1>
    </div>
    <br>
    <p style="{$this->styleSkFontEmail}">AYSO WELCOMES YOU TO WAIPIO PENINSULA SOCCER COMPLEX, WAIPAHU, HAWAII<br>June 
    30 - July 7, 2019</p>
    <div style="{$this->styleClearBoth}"></div>
  </center>
  <hr>Thank you for registering to volunteer at the 2019 National Games!
  <p style="{$this->stylePEmail}">Thank you for registering to volunteer at the 2019 National Games!</p>
  <br>
  {$this->renderHtmlPerson($personView)}

  {$this->renderHtmlGeneralInformation()}
    
    <p style="{$this->styleP}">
      I will provide additional updates in the coming weeks. 
      Please drop me a note if you have any questions about officiating at the Games or with suggestions you have on how we can 
      better communicate the information you need.
    </p>

    <p style="{$this->styleP}">I look forward to meeting you at the Games.</p>

    <p style="{$this->styleP}">Sincerely,</p>
    
    <p style="{$this->styleP}">{$this->escape($refAdmin->name)}<br>
      2019 National Games Referee Administrator<br>
      <a href="mailto:{$refAdmin->email}?subject={$refAdmin->subject}">{$refAdmin->email}</a>
    </p>
</body>
</html>
EOD;
    }
    private function renderHtmlGeneralInformation() {
        return <<<EOT
  <p style="{$this->stylePStrong}">
    General Information
  </p>
  <p style="{$this->styleP}">
    As you might expect, we have a full calendar of soccer and related activities starting on Monday, July 1 and 
    running through Sunday, July 7. On Monday, July 1, 2019, we will have a mandatory meeting for Coaches and 
    Referees at the <b>Waipio Soccer Complex</b> to provide information to all coaches and referees on how to have a 
    successful National Games. We will review important rules, procedures, safety and more! You will also receive 
    your coach and referee bags at this meeting. A full calendar may be viewed at <a href="http://aysonationalgames.org/schedule-of-events/" target="_blank">National Games Calendar</a>.  General information about the Games can 
    be found at <a href="http://www.aysonationalgames.org/" target="_blank">National Games</a>. Soccer fest will be 
    held on 02 July and referees will be able to volunteer for those games in zAYSO.  Pool play games will begin on Wednesday 03 July.
    </p>
EOT;
    } 
    private function renderHtmlPerson(RegPersonViewDecorator $personView)
    {
        $regYearProject = $this->currentProject->regYear;

        $href = $this->generateUrlAbsoluteUrl('app_welcome');
        
        $notes = nl2br($this->escape($personView->notesUser));
        
        $msg = <<<EOD
<table style="{$this->tableClass}">
  <tr><td>Name          </td><td>{$this->escape($personView->name)} </td></tr>
  <tr><td>Email         </td><td>{$this->escape($personView->email)}</td></tr>
  <tr><td>Phone         </td><td>{$personView->phone}</td></tr>
  <tr><td>Will Referee  </td><td>{$personView->willRefereeBadge}</td></tr>
  <tr><td>Will Volunteer</td><td>{$personView->willVolunteer}</td></tr>
  <tr><td>Will Coach    </td><td>{$personView->willCoach}</td></tr>
  <tr>
    <td>AYSO ID</td>
    <td>{$personView->fedId}</td>
  </tr><tr>
    <td>Section/Area/Region</td>
    <td style="{$personView->getOrgKeyStyle()}">{$personView->orgId}</td>
  </tr><tr>
    <td>Membership Year</td>
    <td style="{$personView->getRegYearStyle($regYearProject)}">{$personView->getRegYear($regYearProject)}</td>
  </tr><tr>
    <td>Referee Badge</td>
    <td style="{$personView->getCertStyle('CERT_REFEREE')}">{$personView->getCertBadge('CERT_REFEREE')}</td>
  </tr><tr>
    <td>Safe Haven</td>
    <td style="{$personView->getCertStyle('CERT_SAFE_HAVEN')}">{$personView->getCertBadge('CERT_SAFE_HAVEN')}</td>
  </tr><tr>
    <td>Concussion Aware</td>
    <td style="{$personView->getCertStyle('CERT_CONCUSSION')}">{$personView->getCertBadge('CERT_CONCUSSION')}</td>
  </tr>
  <tr><td>User Notes</td><td>{$notes}</td></tr>
  <tr><td style="{$this->styleTd}" colspan="2" ><a href="{$href}">Update Tournament Plans or Availability</a></td></tr>
</table>
<br>
EOD;

    if ($personView->person->needsCerts()) {
        $msg .= <<<EOT
  <p style="{$this->stylePStrong}">
    Please Review Your Certifications
  </p>

<p style="{$this->styleP}">
    If you plan to referee (or volunteer) then please ensure your eAYSO information is up to date.
    Anything above that is marked with <span style="{$personView->dangerStyle}">***</span> requires your action.
</p>

<p style="{$this->styleP}">
    Please notify me if your eAYSO information does not match the above information and I will update zAYSO accordingly.
</p>

<p style="{$this->styleP}">
    This training takes about 30-60 minutes.  Please take time today and complete this training.
    When it's done, your records will be updated and you'll be ready to join us at the National Games.
</p>
EOT;
    }
    
    if ($personView->person->needsCertSafeHaven()) {
        $msg .= <<<EOT
  <p style="{$this->stylePStrong}">
    AYSO Safe Haven
  </p>
<p style="{$this->styleP}">
    AYSO requires all participating referees and coaches to have completed <strong>the most current</strong> AYSO Safe Haven training.
    If you have yet to complete the latest version of this training, the AYSO Safe Haven Training is available online
    . First, sign into <a href="https://aysou.org" target="_blank">https://aysou.org</a>
    and then access the AYSO Safe Haven Course under "My Courses > AYSO's Safe Haven".
</p>
EOT;
    }
    
    if ($personView->person->needsCertConcussion()) {
        $msg .= <<<EOT
  <p style="{$this->stylePStrong}">
    AYSO Concussion Awareness
  </p>
<p style="{$this->styleP}">
    All participating referees and coaches are required to have completed CDC Concussion Awareness training.
    If you have yet to complete training, the AYSO CDC Concussion Training Course is available online. First, sign 
    into <a href="https://aysou.org" target="_blank">https://aysou.org</a>.  After login, look under 
    "My Courses > CDC: Concussion Course".
</p>
EOT;
    }
    
    return $msg;
    }
}