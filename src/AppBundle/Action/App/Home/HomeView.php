<?php

namespace AppBundle\Action\App\Home;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeView extends AbstractView
{
    private $user;

    /** @var  ProjectPerson */
    private $projectPerson;

    /** @var ProjectPersonRepositoryV2  */
    private $projectPersonRepository;

    private $projectPersonViewDecorator;

    public function __construct(
        ProjectPersonRepositoryV2  $projectPersonRepository,
        ProjectPersonViewDecorator $projectPersonViewDecorator
    )
    {
        $this->projectPersonRepository    = $projectPersonRepository;
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
    }
    public function __invoke(Request $request)
    {
        $this->user = $user = $this->getUser();
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        $this->projectPerson = $this->projectPersonRepository->find($projectKey,$personKey);

        if (!$this->projectPerson) {
            throw new \LogicException('No project person in the home view for ' . $user['name']);
        }
        $this->projectPersonViewDecorator->setProjectPerson($this->projectPerson);

        return new Response($this->renderPage());
    }
    protected function renderPage()
    {
        $content = <<<EOD
{$this->renderNotes()}<br />
<div class="account-person-list">
{$this->renderAccountInformation()}
{$this->renderRegistration()}
{$this->renderAysoInformation()}
{$this->renderAvailability()}
</div>
EOD;

        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }

    /* ====================================================
     * Account Information
     * TODO Move to own teamplate
     */

    private function renderAccountInformation()
    {
        $user = $this->user;

        return <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">Zayso Account Information</th></tr>
  <tr><td>Account Name </td><td>{$user['name']}</td></tr>
  <tr><td>Account User </td><td>{$user['username']}</td></tr>
  <tr><td>Account Email</td><td>{$user['email']}</td></tr>
  <!--
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('user_update')}">
        Update My zAYSO Account
    </a>
  </td></tr>
  -->
</table>
EOD;
    }
    private function renderRegistration()
    {
        $personView = $this->projectPersonViewDecorator;

        return <<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">Registration Information</th></tr>
  <tr><td>Registration Name </td><td>{$this->escape($personView->name) }</td></tr>
  <tr><td>Registration Email</td><td>{$this->escape($personView->email)}</td></tr>
  <tr><td>Registration Phone</td><td>{$this->escape($personView->phone)}</td></tr>
  <tr><td>Will Referee  </td><td>{$personView->willRefereeBadge}</td></tr>
  <tr><td>Will Volunteer</td><td>{$personView->willVolunteer}   </td></tr>
  <tr><td>Will Coach    </td><td>{$personView->willCoach}       </td></tr>
  <tr class="trAction"><td class="text-center" colspan="2">
    <a href="{$this->generateUrl('project_person_update')}">
        Update My Plans or Availability
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderAvailability()
    {
        $person = $this->projectPerson;
        if (!$person->isReferee()) {
            return null;
        }
        $personView = $this->projectPersonViewDecorator;
        
        return <<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">Availability Information</th></tr>
  <tr><td>Available Wed (Soccerfest) </td><td>{$personView->availWed}     </td></tr>
  <tr><td>Available Thu (Pool Play)  </td><td>{$personView->availThu}     </td></tr>
  <tr><td>Available Fri (Pool Play)  </td><td>{$personView->availFri}     </td></tr>
  <tr><td>Available Sat Morning  (PP)</td><td>{$personView->availSatMorn} </td></tr>
  <tr><td>Available Sat Afternoon(QF)</td><td>{$personView->availSatAfter}</td></tr>
  <tr><td>Available Sun Morning  (SF)</td><td>{$personView->availSunMorn }</td></tr>
  <tr><td>Available Sun Afternoon(FM)</td><td>{$personView->availSunAfter}</td></tr>
  <tr class="trAction"><td class="text-center" colspan="2">
    <a href="{$this->generateUrl('project_person_update')}">
        Update My Plans or Availability
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderAysoInformation()
    {
        $personView = $this->projectPersonViewDecorator;

        return <<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">AYSO Information</th></tr>
  <tr><td>AYSO ID</td>            <td>{$personView->fedId}</td></tr>
  <tr><td>Membership Year</td>    <td>{$personView->regYear}</td></tr>
  <tr><td>Referee Badge</td>      <td>{$personView->refereeBadge}</td></tr>
  <tr><td>Safe Haven</td>         <td>{$personView->safeHavenCertified}</td></tr>
  <tr><td>Concussion Aware   </td><td>{$personView->concussionAware}</td></tr>
  <tr><td>Section/Area/Region</td><td>{$personView->sar}</td></tr>
</table>
EOD;
    }
    /* ==========================================
     * Rest of the info, break out later
     *
     */
    protected function renderMoreInformation()
    {
        return <<<EOD
<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Teams</th></tr>
  <tr><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/teams?_back=%2Fhome">Add/Remove Teams
    </a>
  </td></tr>
</table>

<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Crew</th></tr>
  <tr><td style="text-align: center;" colspan="2">
    Primary: Art Hundiak
  </td></tr>
  <tr><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/persons?_back=%2Fhome">Add/Remove People
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderNotes()
    {
        return <<<EOD
<div id="notes">
  <legend>Thank you for registering to Referee at the 2016 National Games!</legend>
  <p>
    Review your plans for the National Games to ensure we understand your availability and the roles you expect to play during the Games. 
    Update your plans and availability at any time.
  </p><br/>  
  <p>Discounted hotel reservations are now available for the AYSO National Games 2016!  
    Information on accommodations is available on the National Site by 
    <a href="http://www.aysonationalgames.org/Default.aspx?tabid=730869" target="_blank">clicking here</a>.
  </p>
</div>
EOD;
    }
}
