<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

class RegisterTemplateEmail extends AbstractView2
{
    private $projectPersonViewDecorator;

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
        width: 400px;
        border: 2px solid black;
        margin-bottom: 0px;
    ';
    protected $styleBodyEmail = 'width: inherit;';
    protected $stylePEmail = '
        font-size: 12px;
        text-align: center;
    ';
    protected $styleP = '
        margin-bottom: 0.5em;
    ';
    protected $styleClearBoth = '
        clear: both
    ';

    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator
    )
    {
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
    }

    public function renderHtml($personArray)
    {
        $person = new ProjectPerson();
        $person = $person->fromArray($personArray);
        $personView = $this->projectPersonViewDecorator;
        $personView->setProjectPerson($person);

        return <<<EOD
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=us-ascii">
</head>
<body style="{$this->styleBodyEmail}" >
  <center>
    <div style="{$this->styleSkHeader}">
      <h1>
          <img src="http://ng2016register.zayso.org/images/header-ipad_01.png" width="70%">
      </h1>
    </div>
    <br>
    <p style="{$this->styleSkFontEmail}">AYSO WELCOMES YOU TO PALM BEACH COUNTY, FLORIDA, JULY 5-10, 2016</p>
    <div style="{$this->styleClearBoth}"></div>
  </center>
  <hr>
  <p style="{$this->stylePEmail}">Thank you for registering to volunteer at the 2016 National Games!</p>
  <br>
  {$this->renderHtmlPerson($personView)}

  <p style="{$this->styleP}">
    As you might expect, we have a full calendar of soccer and related activities starting Tuesday, July 5 and 
    running through Sunday, July 10. 
    On Tuesday, July 5, 2016, we will have a mandatory meeting for Coaches and Referees at the 
    <a href="http://www3.hilton.com/en/hotels/florida/hilton-west-palm-beach-PBIWPHH/index.html" target="_blank">Hilton West Palm Beach</a> 
    to provide information to all coaches and referees on how to have a successful National Games. 
    We will review important roles, procedures, safety and more! You will also receive your coach and referee bags at this meeting.
    A full calendar may be viewed at <a href="http://aysonationalgames.org/schedule-of-events/" target="_blank">National Games Calendar</a>.
    General information about the Games can be found at <a href="http://www.aysonationalgames.org/" target="_blank">National Games</a>.
    </p>
    <p style="{$this->styleP}">
      I will provide additional updates in the coming weeks. 
      Please drop me a note if you have any questions about officiating at the Games or with suggestions you have on how we can 
      better communicate the information you need.
    </p>

    <p style="{$this->styleP}">I look forward to meeting you at the Games.</p>

    <p style="{$this->styleP}">Sincerely,</p>
    
    <p style="{$this->styleP}">Tom Bobadilla<br>
      National Referee Administrator<br>
      2016 National Games Referee Administrator<br>
      <a href="mailto:ThomasBobadilla@ayso.org?subject=Question%20about%20the%202016%20National%Games">ThomasBobadilla@ayso.org</a>
    </p>
</body>
</html>
EOD;
    }
    private function renderHtmlPerson(ProjectPersonViewDecorator $personView)
    {
        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

        $href = $this->generateUrlAbsoluteUrl('app_welcome');
        
        $notes = nl2br($this->escape($personView->notesUser));
        
        return <<<EOD
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
    <td style="{$personView->getOrgKeyStyle()}">{$personView->orgKey}</td>
  </tr><tr>
    <td>Membership Year</td>
    <td style="{$personView->getRegYearStyle($regYearProject)}">{$personView->getRegYear($regYearProject)}</td>
  </tr><tr>
    <td>Safe Haven</td>
    <td style="{$personView->getCertStyle('CERT_SAFE_HAVEN')}">{$personView->getCertBadge('CERT_SAFE_HAVEN')}</td>
  </tr><tr>
    <td>Referee Badge</td>
    <td style="{$personView->getCertStyle('CERT_REFEREE')}">{$personView->getCertBadge('CERT_REFEREE')}</td>
  </tr><tr>
    <td>Concussion Aware</td>
    <td style="{$personView->getCertStyle('CERT_CONCUSSION')}">{$personView->getCertBadge('CERT_CONCUSSION')}</td>
  </tr>
  <tr><td>User Notes</td><td>{$notes}</td></tr>
  <tr><td colspan="2"><a href="{$href}">Update Tournament Plans or Availability</a></td></tr>
</table>
<br>
<p style="{$this->styleP}">
    If you plan to referee (or volunteer) then please ensure your eAYSO information is up to date.
    Anything above that is marked with <span style="{$personView->dangerStyle}">***</span> needs action.
</p>
<p style="{$this->styleP}">
    AYSO requires all participating referees and coaches to have completed AYSO Safe Haven training.
    If you have yet to complete training, the AYSO Safe Haven Training Course is available online. First, sign into <a href="https://www.aysotraining.org" target="_blank">https://www.aysotraining.org</a>
    with your eAYSO ID and last name. Then you can access the AYSO Safe Haven Training Course at <a href="https://www.aysotraining.org/training/safehaven/aysosafehaven.asp?course=safehaven" target="_blank">https://www.aysotraining.org/training/safehaven/aysosafehaven.asp?course=safehaven</a>.
</p>
<p style="{$this->styleP}">
    By Florida law, all participating referees and coaches are required to have completed CDC Concussion training.
    If you have yet to complete training, the AYSO CDC Concussion Training Course is available online. First, sign into <a href="https://www.aysotraining.org" target="_blank">https://www.aysotraining.org</a>
    with your eAYSO ID and last name. Then you can access the AYSO CDD Concussion Training Course at <a href="https://www.aysotraining.org/training/CDC/cdcfiles/cdc.asp" target="_blank">https://www.aysotraining.org/training/CDC/cdcfiles/cdc.asp</a>.
</p>
<p style="{$this->styleP}">
    These training modules takes about 30 minutes each.  If your record is Please take a time today and complete this training.
    When it's done, your records will be updated and you'll be ready to join us at the National Games.
</p>
<p style="{$this->styleP}">
    Please notify the referee administrator if your eAYSO information does not match the above information.
    I will update zAYSO accordingly.
</p>
EOD;
    }
}