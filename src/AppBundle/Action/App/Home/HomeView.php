<?php

namespace AppBundle\Action\App\Home;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\VolunteerKeyTransformer;
use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\Action\Project\Person\ProjectPersonRepository;

use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeView extends AbstractView
{
    private $user;
    private $projectPerson;
    private $projectPersonRepository;

    private $fedKeyTransformer;
    private $orgKeyTransformer;

    public function __construct(
        ProjectPersonRepository $projectPersonRepository,
        VolunteerKeyTransformer $fedKeyTransformer,
        RegionToSarTransformer  $orgKeyTransformer
    )
    {
        $this->fedKeyTransformer = $fedKeyTransformer;
        $this->orgKeyTransformer = $orgKeyTransformer;
        $this->projectPersonRepository = $projectPersonRepository;
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
    /* ====================================================
     * Account Information
     *
     */
    private function renderAccountInformation()
    {
        $user = $this->user;

        return <<<EOD
<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">Zayso Account Information</th></tr>
  <tr><td>Name:   </td><td>{$user['name']}</td></tr>
  <tr><td>Account:</td><td>{$user['email']}</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('user_update')}">
        Update My Zayso Account
    </a>
  </td></tr>
</table>
EOD;
    }
    private function renderPlans()
    {
        $projectPerson = $this->projectPerson;

        $plans = isset($projectPerson['plans'])   ? $projectPerson['plans'] : null;

        $willAttend    = isset($plans['willAttend'])    ? $plans['willAttend']    : 'Unknown';
        $willReferee   = isset($plans['willReferee'])   ? $plans['willReferee']   : 'No';
        $willVolunteer = isset($plans['willVolunteer']) ? $plans['willVolunteer'] : 'No';

        // Should have transformers
        $willAttend    = ucfirst($willAttend);
        $willReferee   = ucfirst($willReferee);
        $willVolunteer = ucfirst($willVolunteer);

        $badge = null;

        if ($willReferee != 'No') {
            $badge =
                isset($projectPerson['roles']['ROLE_REFEREE']) ?
                    $projectPerson['roles']['ROLE_REFEREE']['badge'] :
                    null;
            if ($badge) {
                $willReferee = sprintf('%s (%s)',$willReferee,$badge);
            }
        }
        return <<<EOD
<table class="account-person-list app_table" border="1">
  <tr><th colspan="2">Tournament Plans</th></tr>
  <tr><td>Will Attend:   </td><td>{$willAttend}   </td></tr>
  <tr><td>Will Referee:  </td><td>{$willReferee}  </td></tr>
  <tr><td>Will Volunteer:</td><td>{$willVolunteer}</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('project_person_update')}">
        Update My Plans
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
<table  class="account-person-list app_table" border="1" >
  <tr><th colspan="2">AYSO Information</th></tr>
  <tr><td>AYSO ID:</td>            <td>{$fedId}</td></tr>
  <tr><td>Membership Year:</td>    <td>{$regYear}</td></tr>
  <tr><td>Referee Badge:</td>      <td>{$badge}</td></tr>
  <tr><td>Section/Area/Region:</td><td>{$org}</td></tr>
  <tr><td style="text-align: center;" colspan="2">
    <a href="{$this->generateUrl('project_person_update_fed')}">
        Update My AYSO Information
    </a>
  </td></tr>
   
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
