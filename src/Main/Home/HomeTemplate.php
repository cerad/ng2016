<?php declare(strict_types=1);

namespace Zayso\Main\Home;

use AppBundle\Action\Project\User\ProjectUser;

use Zayso\Common\Contract\TemplateInterface;
use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;

use Zayso\Project\Project;
use Zayso\Project\ProjectInterface;
use Zayso\Reg\Person\RegPerson;
use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersonViewDecorator;

class HomeTemplate implements TemplateInterface
{
    use EscapeTrait;
    use RouterTrait;
    use AuthenticationTrait;

    private $regPersonView;
    private $regPersonFinder;

    public function __construct(
        RegPersonFinder        $regPersonFinder,
        RegPersonViewDecorator $regPersonViewDecorator
    ) {
        $this->regPersonFinder  = $regPersonFinder;
        $this->regPersonView    = $regPersonViewDecorator;
    }
    // TODO Pull user and project from RegPerson
    public function render(ProjectUser $user, ProjectInterface $project, RegPerson $regPerson) : string
    {
        $regPersonView = $this->regPersonView;
        $regPersonView->setPerson($regPerson);

        $content = <<<EOD
{$this->renderNotes($project)}<br />
<div class="account-person-list">
{$this->renderAccountInformation($user)}
{$this->renderRegistration($regPersonView)}
{$this->renderCrewInformation($regPersonView)}
{$this->renderTeamInformation($regPersonView)}
{$this->renderAysoInformation($project,$regPersonView)}
{$this->renderAvailability($regPersonView)}
</div>
<div>
{$this->renderInstructions($regPersonView)}
</div>
<div>
{$this->renderHotelInformation()}
</div>
EOD;
        return $project->pageTemplate->render($content);
    }

    /* ====================================================
     * Crew Information
     */
    private function renderCrewInformation(RegPersonViewDecorator $regPersonView)
    {
        $regPersonPersons = $this->regPersonFinder->findRegPersonPersons($regPersonView->regPersonId);

        $html = <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">My Crew</th></tr>
EOD;

        foreach ($regPersonPersons as $regPersonPerson) {
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
    private function renderTeamInformation(RegPersonViewDecorator $regPersonView) : string
    {
        $regPersonTeams = $this->regPersonFinder->findRegPersonTeams($regPersonView->regPersonId);

        $html = <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">My Teams</th></tr>
EOD;

        foreach ($regPersonTeams as $regPersonTeam) {
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
     * TODO Move to own template
     */
    private function renderAccountInformation(ProjectUser $user) : string
    {
        return <<<EOD
<table class="tableClass" >
  <tr><th colspan="2" style="text-align: center;">zAYSO Account Information</th></tr>
  <tr><td>Account Name </td><td>{$user->name}</td></tr>
  <tr><td>Account User </td><td>{$user->username}</td></tr>
  <tr><td>Account Email</td><td>{$user->email}</td></tr>
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

    private function renderRegistration(RegPersonViewDecorator $personView)
    {
        return <<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">Registration Information</th></tr>
  <tr><td>Registration Name </td><td>{$this->escape($personView->name)}</td></tr>
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

    private function renderAvailability(RegPersonViewDecorator $personView) : string
    {
        if (!$personView->isReferee) {
            return '';
        }
        // Todo - pull the availability list from the project
        return
            <<<EOD
<table class="tableClass">
  <tr><th colspan="2" style="text-align: center;">Availability Information</th></tr>
  <tr><td>Available Tue (Soccerfest)  </td><td>{$personView->availTue}</td></tr>
  <tr><td>Available Wed (Pool Play)   </td><td>{$personView->availWed}</td></tr>
  <tr><td>Available Thu (Pool Play)   </td><td>{$personView->availThu}</td></tr>
  <tr><td>Available Fri (Pool Play)   </td><td>{$personView->availFri}</td></tr>
  <tr><td>Available Sat Morning   (PP)</td><td>{$personView->availSatMorn}</td></tr>
  <tr><td>Available Sat Afternoon (QF)</td><td>{$personView->availSatAfter}</td></tr>
  <tr><td>Available Sun Morning   (SF)</td><td>{$personView->availSunMorn}</td></tr>
  <tr><td>Available Sun Afternoon (FM)</td><td>{$personView->availSunAfter}</td></tr>
  <tr class="trAction"><td class="text-center" colspan="2">
    <a href="{$this->generateUrl('project_person_update')}">
        Update My Plans or Availability
    </a>
  </td></tr>
</table>
EOD;
    }

    // TODO Key this off of roles
    private function renderAysoInformation(ProjectInterface $project, RegPersonViewDecorator $personView) : string
    {
        $regYearProject = $project->regYear;

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
    // This might be a candidate for a project specific template
    private function renderNotes(Project $project) : string
    {
        return
            <<<EOD
<div id="notes">
  <legend>Thank you for registering to Volunteer at the {$project->title}!</legend>
  <p>
    Review your plans for the tournament to ensure we understand your availability and the roles you expect to play during the tournament.
   </p>
<br>
   <p>
    If you are not already registered as an AYSO volunteer for {$project->regYear}, please visit the <a href="https://www.aysosection7
.org/Default.aspx?tabid=724814" target="_blank">Section 7 website</a> and 
register as a volunteer for the {$project->title}, or go to your Region website and register as an AYSO volunteer. 
</p>
<br>
   <p>
    Update your plans and availability at any time.
   </p>  
</div>
EOD;
    }
    private function renderInstructions(RegPersonViewDecorator $personView) : string
    {
        $html = null;
        if ($personView->isReferee) {
            $html =
                <<<EOT
<div id="clear-fix">
    <legend>Instructions for Referees</legend>
      <ul class="cerad-common-help ul_bullets">
            <li>Click on "<a href="{$this->generateUrl('schedule_official_2016')}">Request Assignments</a>" under the "Referees" menu item above.</li>
            <li>On any open match, click on the position you'd like to request, e.g. REF, AR1, AR2</li>
            <li>Click "Submit" button"</li>
            <li>Check back on your schedule under "<a href="{$this->generateUrl('schedule_my_2016')}">My Schedule</a>" under the "My Stuff" menu item above to see the assignments.
            <li>Detailed instructions for self-assigning are available <a href="{$this->generateUrl(
                    'detailed_instruction'
                )}" target="_blank">by clicking here</a>.</ul>
      </ul>
</div>
<hr>
EOT;
        }

        return $html;
    }
    private function renderHotelInformation() : string
    {
        return
            <<<EOT
<legend>Referee Hotel Discounts</legend>
<p>Information on booking discounted travel can be found at <a href="https://aysonationalgames.org/travel-information/" 
target="_blank">https://aysonationalgames.org/travel-information/</a></p>
EOT;
    }
}
