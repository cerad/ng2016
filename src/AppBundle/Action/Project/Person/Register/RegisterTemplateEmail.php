<?php
namespace AppBundle\Action\Project\Person\Register;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

class RegisterTemplateEmail extends AbstractView2
{
    private $projectPersonViewDecorator;

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
  <link rel="stylesheet" type="text/css" href="http://ng2016register.zayso.org/css/zayso.css" media="all">
  <title></title>
</head>
<body class="email" >
  <center>
    <div class="skHeader">
      <h1>
          <img src="http://ng2016register.zayso.org/images/header-ipad_01.png" width="70%">
      </h1>
    </div>
    <br>
    <p class="skFont email">AYSO WELCOMES YOU TO PALM BEACH COUNTY, FLORIDA, JULY 5-10, 2016</p>
    <div class="clear-both"></div>
  </center>
  <hr>
  <p class="email">Thank you for registering to volunteer at the 2016 National Games!</p>
  <br>
  {$this->renderHtmlPerson($personView)}
  <br>
  <p>
    As you might expect, we have a full calendar of soccer and related activities starting Tuesday, July 5 and 
    running through Sunday, July 10. 
    On Tuesday, July 5, 2016, we will have a mandatory meeting for Coaches and Referees at the 
    <a href="http://www3.hilton.com/en/hotels/florida/hilton-west-palm-beach-PBIWPHH/index.html" target="_blank">Hilton West Palm Beach</a> 
    to provide information to all coaches and referees on how to have a successful National Games. 
    We will review important roles, procedures, safety and more! You will also receive your coach and referee bags at this meeting.
    A full calendar may be viewed at <a href="http://aysonationalgames.org/schedule-of-events/" target="_blank">National Games Calendar</a>.
    General information about the Games can be found at <a href="http://www.aysonationalgames.org/" target="_blank">National Games</a>.
    </p>
    <br>
    <p>
      I will provide additional updates in the coming weeks. 
      Please drop me a note if you have any questions about officiating at the Games or with suggestions you have on how we can 
      better communicate the information you need.
    </p>
    <br>
    <p>I look forward to meeting you at the Games.</p>
    <br>
    <p>Sincerely,</p>
    
    <p>Tom Bobadilla<br>
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
        $href = $this->generateUrlAbsoluteUrl('app_welcome');
        
        $notes = nl2br($this->escape($personView->notesUser));

        return <<<EOD
<table class="tableClass">
  <tr><td>Name          </td><td>{$this->escape($personView->name)} </td></tr>
  <tr><td>Email         </td><td>{$this->escape($personView->email)}</td></tr>
  <tr><td>Phone         </td><td>{$personView->phone}</td></tr>
  <tr><td>Will Referee  </td><td>{$personView->willRefereeBadge}</td></tr>
  <tr><td>Will Volunteer</td><td>{$personView->willVolunteer}</td></tr>
  <tr><td>Will Coach    </td><td>{$personView->willCoach}</td></tr>
  <tr><td>AYSO ID       </td><td>{$personView->fedId}</td></tr>
  <tr><td>Mem Year      </td><td>{$personView->regYear}</td></tr>
  <tr><td>Referee Badge </td><td>{$personView->refereeBadge}</td></tr>
  <tr><td>Safe Haven    </td><td>{$personView->safeHavenCertified}</td></tr>
  <tr><td>Concussion    </td><td>{$personView->concussionAware}</td></tr>
  <tr><td>SAR           </td><td>{$personView->sar}</td></tr>
  <tr><td>User Notes    </td><td>{$notes}</td></tr>
  <tr><td colspan="2"><a href="{$href}">Update Tournament Plans or Availability</a></td></tr>
</table>
EOD;
    }
}