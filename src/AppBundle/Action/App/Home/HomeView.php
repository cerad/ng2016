<?php

namespace AppBundle\Action\App\Home;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;
use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;

use AppBundle\Action\Project\User\ProjectUser;
use AppBundle\Action\RegPerson\RegPersonFinder;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Action\InstructionsView;

class HomeView extends AbstractView2
{
    /** @var  ProjectUser */
    private $user;

    /** @var  ProjectPerson */
    private $projectPerson;

    /** @var ProjectPersonRepositoryV2  */
    private $projectPersonRepository;

    private $projectPersonViewDecorator;

    private $regPersonFinder;

    private $instructionsView;

    public function __construct(
        ProjectPersonRepositoryV2  $projectPersonRepository,
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        RegPersonFinder $regPersonFinder
    )
    {
        $this->regPersonFinder = $regPersonFinder;

        $this->projectPersonRepository    = $projectPersonRepository;
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;

        $this->instructionsView = new InstructionsView;
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

        return $this->newResponse($this->renderPage());
    }
    protected function renderPage()
    {
        $content = <<<EOD
{$this->renderNotes()}<br />
<div class="account-person-list">
{$this->renderAccountInformation()}
{$this->renderRegistration()}
{$this->renderCrewInformation()}
{$this->renderTeamInformation()}
{$this->renderAysoInformation()}
{$this->renderAvailability()}
</div>
<div>
{$this->renderInstructions()}
</div>
<div>
{$this->renderHotelInformation()}
</div>
EOD;
        return $this->renderBaseTemplate($content);
    }

    /* ====================================================
     * Crew Information
     */
    private function renderCrewInformation()
    {
        $regPersonPersons = $this->regPersonFinder->findRegPersonPersons($this->user->getRegPersonId());

        $html = <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">My Crew</th></tr>
EOD;

        foreach($regPersonPersons as $regPersonPerson) {
            $html .= <<<EOD
  <tr><td>{$regPersonPerson->role}</td><td>{$regPersonPerson->memberName}</td></tr>
EOD;
        }
        $html .= <<<EOD
  <tr class="trAction"><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('reg_person_persons_update')}">
        Add/Remove People
    </a>
  </td></tr>
</table>
EOD;
        return $html;
    }
    /* ====================================================
     * Crew Information
     */
    private function renderTeamInformation()
    {
        $regPersonTeams = $this->regPersonFinder->findRegPersonTeams($this->user->getRegPersonId());

        $html = <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">My Teams</th></tr>
EOD;

        foreach($regPersonTeams as $regPersonTeam) {
            $html .= <<<EOD
  <tr><td>{$regPersonTeam->role}</td><td>{$regPersonTeam->teamName}</td></tr>
EOD;
        }
        $html .= <<<EOD
  <tr class="trAction"><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('reg_person_teams_update')}">
        Add/Remove Teams
    </a>
  </td></tr>
</table>
EOD;
        return $html;
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
  <tr><th colspan="2" style="text-align: center;">zAYSO Account Information</th></tr>
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
        
        return
<<<EOD
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
    // TODO Key this off of roles
    private function renderAysoInformation()
    {
        $personView = $this->projectPersonViewDecorator;

        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

        return
<<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">AYSO Information</th></tr>
  <tr>
    <td>AYSO ID</td>
    <td>{$personView->fedId}</td>
  </tr><tr>
    <td>Section/Area/Region</td>
    <td class="{$personView->getOrgKeyClass()}">{$personView->orgKey}</td>
  </tr><tr>
    <td>Membership Year</td>
    <td class="{$personView->getRegYearClass($regYearProject)}">{$personView->getRegYear($regYearProject)}</td>
  </tr><tr>
    <td>Safe Haven</td>
    <td class="{$personView->getCertClass('CERT_SAFE_HAVEN')}">{$personView->getCertBadge('CERT_SAFE_HAVEN')}</td>
  </tr><tr>
    <td>Referee Badge</td>
    <td class="{$personView->getCertClass('CERT_REFEREE')}">{$personView->getCertBadge('CERT_REFEREE')}</td>
  </tr><tr>
    <td>Concussion Aware</td>
    <td class="{$personView->getCertClass('CERT_CONCUSSION')}">{$personView->getCertBadge('CERT_CONCUSSION')}</td>
  </tr>
</table>
EOD;
    }
    /* ==========================================
     * Rest of the info, break out later
     *
     */
    protected function renderMoreInformation()
    {
        return
<<<EOD
<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Teams</th></tr>
  <tr class="trAction"><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/teams?_back=%2Fhome">Add/Remove Teams
    </a>
  </td></tr>
</table>

<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">My Crew</th></tr>
  <tr><td style="text-align: center;" colspan="2">
    Primary: Art Hundiak
  </td></tr>
  <tr class="trAction"><td style="text-align: center;" colspan="2" >
    <a href="/project/natgames/person/1/persons?_back=%2Fhome">Add/Remove People
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderNotes()
    {
        return
<<<EOD
<div id="notes">
  <legend>Thank you for registering to Volunteer at the 2019 National Games!</legend>
  <p>
    Review your plans for the National Games to ensure we understand your availability and the roles you expect to play during the Games.
    </p>
    <p>
    Update your plans and availability at any time.
    </p>  
</div>
EOD;
    }
    private function renderInstructions()
    {
        $personView = $this->projectPersonViewDecorator;

        $isReferee = $personView->getCertBadge('CERT_REFEREE');
        
        $html = null;
        if ($isReferee) {
            $html = 
<<<EOT
<div id="clear-fix">
    <legend>Instructions for Referees</legend>
      <ul class="cerad-common-help ul_bullets">
            <li>Click on "<a href="{$this->generateUrl('schedule_official_2016')}">Request Assignments</a>" under the "Referees" menu item above.</li>
            <li>On any open match, click on the position you'd like to request, e.g. REF, AR1, AR2</li>
            <li>Click "Submit" button"</li>
            <li>Check back on your schedule under "<a href="{$this->generateUrl('schedule_my_2016')}">My Schedule</a>" under the "My Stuff" menu item above to see the assignments.
            <li>Detailed instructions for self-assigning are available <a href="{$this->generateUrl('detailed_instruction')}" target="_blank">by clicking here</a>.</ul>
      </ul>
</div>
<hr>
EOT;
        }
        
        return $html;
    }
    private function renderHotelInformation()
    {
        return
<<<EOT
<legend>Referee Hotel Discounts</legend>
<p>Information on booking discounted travel can be found at <a href="https://aysonationalgames.org/travel-information/" 
target="_blank">https://aysonationalgames.org/travel-information/</a></p>
EOT;
    }
}
