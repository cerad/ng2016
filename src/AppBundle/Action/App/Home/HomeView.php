<?php

namespace AppBundle\Action\App\Home;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\VolunteerKeyTransformer;
//  AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Project\Person\ProjectPersonRepository;

use AppBundle\Action\Project\Person\ViewTransformer\WillRefereeTransformer;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeView extends AbstractView
{
    private $user;
    private $projectPerson;
    private $projectPersonRepository;

    private $phoneTransformer;
    private $fedKeyTransformer;
    private $orgKeyTransformer;
    private $willRefereeTransformer;

    public function __construct(
        ProjectPersonRepository $projectPersonRepository,
        PhoneTransformer        $phoneTransformer,
        VolunteerKeyTransformer $fedKeyTransformer,
        RegionToSarTransformer  $orgKeyTransformer,
        WillRefereeTransformer  $willRefereeTransformer
    )
    {
        $this->projectPersonRepository = $projectPersonRepository;

        $this->phoneTransformer  = $phoneTransformer;
        $this->fedKeyTransformer = $fedKeyTransformer;
        $this->orgKeyTransformer = $orgKeyTransformer;

        $this->willRefereeTransformer = $willRefereeTransformer;
     }
    public function __invoke(Request $request)
    {
        $this->user = $user = $this->getUser();
        $projectKey = $user['projectKey'];
        $personKey  = $user['personKey'];

        $this->projectPerson = $this->projectPersonRepository->find($projectKey,$personKey);

        if (!$this->projectPerson) {
            throw new InternalErrorException('No project person in the home view for ' . $user['name']);
        }
        return new Response($this->renderPage());
    }
    protected function renderPage()
    {
        $content = <<<EOD
{$this->renderNotes()}<br />
<div class="account-person-list">
{$this->renderAccountInformation()}
{$this->renderPlans()}
{$this->renderAysoInformation()}
</div>
EOD;

        $this->baseTemplate->setContent($content);
        return $this->baseTemplate->render();
    }
    // Sure wish I understood styling better, tried using <style> but no go
    private $tableClass  = 'table table-bordered table=hover table-condensed';
    private $tableStyle  = 'max-width: 400px; border: 2px solid black; margin-bottom: 0px;';
    //private $tdStyleLeft = 'text-align: right; border: 1px solid gray;';

    /* ====================================================
     * Account Information
     * TODO Move to own teamplate
     */

    private function renderAccountInformation()
    {
        $user = $this->user;

        return <<<EOD
<table class="{$this->tableClass}" style="{$this->tableStyle}">
  <tr><th colspan="2" style="text-align: center;">Zayso Account Information</th></tr>
  <tr><td>Account Name </td><td>{$user['name']}</td></tr>
  <tr><td>Account User </td><td>{$user['username']}</td></tr>
  <tr><td>Account Email</td><td>{$user['email']}</td></tr>
  <!--
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('user_update')}">
        Update My Zayso Account
    </a>
  </td></tr>
  -->
</table>
EOD;
    }
    private function renderPlans()
    {
        $person = $this->projectPerson;
        $phone  = $this->phoneTransformer->transform($person['phone']);

        $plans = isset($person['plans']) ? $person['plans'] : null;

        //$willAttend    = isset($plans['willAttend'])    ? $plans['willAttend']    : 'Unknown';
        $willVolunteer = isset($plans['willVolunteer']) ? $plans['willVolunteer'] : 'No';

        // Should have transformers
        //$willAttend    = ucfirst($willAttend);
        $willVolunteer = ucfirst($willVolunteer);
        
        $willRefereeTransformer = $this->willRefereeTransformer;

        $avail = isset($person['avail']) ? $person['avail'] : null;

        $availSatAfter = isset($avail['availSatAfter']) ? $avail['availSatAfter'] : 'No';
        $availSunMorn  = isset($avail['availSunMorn' ]) ? $avail['availSunMorn' ] : 'No';
        $availSunAfter = isset($avail['availSunAfter']) ? $avail['availSunAfter'] : 'No';

        $availSatAfter = ucfirst($availSatAfter);
        $availSunMorn  = ucfirst($availSunMorn );
        $availSunAfter = ucfirst($availSunAfter);

        return <<<EOD
<table class="{$this->tableClass}" style="{$this->tableStyle}">
  <tr><th colspan="2" style="text-align: center;">Registration Information</th></tr>
  <tr><td>Registration Name </td><td>{$this->escape($person['name'])} </td></tr>
  <tr><td>Registration Email</td><td>{$this->escape($person['email'])}</td></tr>
  <tr><td>Registration Phone</td><td>{$this->escape($phone)}</td></tr>
  <tr><td>Will Referee  </td><td>{$willRefereeTransformer($person)}</td></tr>
  <tr><td>Will Volunteer</td><td>{$willVolunteer}</td></tr>
  <tr><td>Available Sat Afternoon(QF)</td><td>{$availSatAfter}</td></tr>
  <tr><td>Available Sun Morning  (SF)</td><td>{$availSunMorn }</td></tr>
  <tr><td>Available Sun Afternoon(FM)</td><td>{$availSunAfter}</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('project_person_update')}">
        Update My Plans or Availability
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderAysoInformation()
    {
        $projectPerson = $this->projectPerson;

        $fedKey = isset($projectPerson['fedKey']) ? $projectPerson['fedKey'] : null;
        $fedId  = $this->fedKeyTransformer->transform($fedKey);

        $orgKey = $projectPerson['orgKey'];
        $org = $this->orgKeyTransformer->transform($orgKey);

        $regYear = $projectPerson['regYear'];

        $badge = isset($projectPerson['roles']['ROLE_REFEREE']) ?
            $projectPerson['roles']['ROLE_REFEREE']['badge'] :
            null;

        return <<<EOD
<table class="{$this->tableClass}" style="{$this->tableStyle}">
  <tr><th colspan="2" style="text-align: center;">AYSO Information</th></tr>
  <tr><td>AYSO ID:</td>            <td>{$fedId}</td></tr>
  <tr><td>Membership Year:</td>    <td>{$regYear}</td></tr>
  <tr><td>Referee Badge:</td>      <td>{$badge}</td></tr>
  <tr><td>Section/Area/Region:</td><td>{$org}</td></tr>
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
<div id="notes" class="????" style="width: 500px;">
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
