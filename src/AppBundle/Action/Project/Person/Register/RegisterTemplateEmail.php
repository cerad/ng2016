<?php

namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

class RegisterTemplateEmail extends AbstractView2
{
    private $projectPersonViewDecorator;

    private $project;

    /* define inline styling for gmail */
    protected $styleSkHeader = '
        position: relative;
        text-align: center;
        font-style: bold;
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

    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator
    ) {
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
    }

    public function renderHtml($personArray)
    {
        $this->project = $this->getCurrentProjectInfo();

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
  <div style="align-content: center">
    <div style="{$this->styleSkHeader}">
      <h1>
          <img src="http://noc2018.cerad.org/images/header-ipad_01.png" width="70%">
      </h1>
    <br>
    <p style="{$this->styleSkFontEmail}">{$this->project['welcome']}</p>
    </div>
    <div style="{$this->styleClearBoth}"></div>
  </div>
  <hr>
  <p style="{$this->stylePEmail}">Thank you for registering to volunteer at the {$this->project['title']}!</p>
  <br>
  {$this->renderHtmlPerson($personView)}

  {$this->renderHtmlGeneralInformation($personView)}
    
    <p style="{$this->styleP}">
      Please drop me a note if you have any questions about officiating at the {$this->project['shortTitle']} or with suggestions you have on how we can 
      better communicate the information you need.
    </p>

    <p style="{$this->styleP}">I look forward to meeting you at the {$this->project['shortTitle']} in July.</p>

    <p style="{$this->styleP}">Sincerely,</p>
    
    <p style="{$this->styleP}">{$this->project['administrator']['name']}<br>
      Referee Administrator / {$this->project['title']}<br>
      <a href="mailto:{$this->project['administrator']['email']}?subject=Question%20about%20the%20{$this->project['title']}">{$this->project['administrator']['email']}</a>
    </p>
</body>
</html>
EOD;
    }

    private function renderHtmlGeneralInformation($personView)
    {
        return <<<EOT
  <p style="{$this->stylePStrong}">
    General Information
  </p>
  <p style="{$this->styleP}">
    Thank you for volunteering! We will have a full calendar of soccer activity starting on Friday, July 13th running through Sunday, July 15th. We will be reaching out to you intermittently throughout the registration period to review your role and update you on the latest information regarding the AYSO National Open Cup. As we get closer to the event, we will outline any required training or meetings that you will need to attend. 
For more general information regarding the tournament, please visit the National Open Cup website, or contact me if you have any specific questions regarding officiating at the event. 
</p>
EOT;
    }

    private function renderHtmlPerson(ProjectPersonViewDecorator $personView)
    {
        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

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
    When it's done, your records will be updated and you'll be ready to join us at the {$this->project['title']}.
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
    If you have yet to complete the latest version of this training, the AYSO Safe Haven Training Course MT-02 is available online. First, sign into <a href="https://www.aysotraining.org" target="_blank">https://www.aysotraining.org</a>
    with your eAYSO ID {$personView->fedId} and your last name. You can then access the AYSO Safe Haven Training Course at <a href="https://www.aysotraining.org/training/safehaven/aysosafehaven.asp?course=safehaven" target="_blank">https://www.aysotraining.org/training/safehaven/aysosafehaven.asp?course=safehaven</a>.
</p>
EOT;
        }

        if ($personView->person->needsCertConcussion()) {
            $msg .= <<<EOT
  <p style="{$this->stylePStrong}">
    AYSO Concussion Awareness
  </p>
<p style="{$this->styleP}">
    By California law, all participating administrators and coaches are required to have completed CDC Concussion Awareness training.  It is strongly recommended for Referees as well.
    If you have yet to complete training, the AYSO CDC Concussion Training Course is available online. First, sign into <a href="https://www.aysotraining.org" target="_blank">https://www.aysotraining.org</a>
    with your eAYSO ID {$personView->fedId} and last name. Then you can access the AYSO CDC Concussion Awareness Training Course at <a href="https://www.aysotraining.org/training/CDC/cdcfiles/cdc.asp" target="_blank">https://www.aysotraining.org/training/CDC/cdcfiles/cdc.asp</a>.
</p>
EOT;
        }

        return $msg;
    }
}