<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin\Listing;

use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\ProjectInterface;
use Zayso\Reg\Person\RegPersons;
use Zayso\Reg\Person\RegPersonViewDecorator;
use Zayso\User\UserFinder;

class AdminListingTemplate
{
    use EscapeTrait;
    use RouterTrait;

    private $certURL = "https://national.ayso.org/Volunteers/ViewCertification?UserName=";
    private $userFinder;
    private $regPersonView;

    public function __construct(
        UserFinder $userFinder,
        RegPersonViewDecorator $regPersonView // This could be project specific
    )
    {
        $this->userFinder = $userFinder;

        $this->regPersonView = $regPersonView;
    }

    public function render(ProjectInterface $project, AdminListingSearchForm $searchForm, RegPersons $regPersons, ?string $displayKey) : string
    {
        $regPersonCount = count($regPersons);

        $content = <<<EOD
<legend>Person Listing, Count: {$regPersonCount}</legend>
{$searchForm->render()}
<br/>
{$this->renderRegPersons($project, $regPersons, $displayKey)}
EOD;
        return $content;
    }
    private function renderRegPersons(ProjectInterface $project, RegPersons $regPersons, ?string $displayKey) : string
    {
        $html = <<<EOD
<table class='table'>
EOD;

        switch($displayKey) {

            case 'Plans':
                $html .= <<<EOD
<tr>
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Plans</th>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
<tr>
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>User Information</th>
</tr>
EOD;
                break;

            case 'Avail':
            case 'Availability':
                $html .= <<<EOD
<tr>
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Availability</th>
</tr>
EOD;
                break;
        }

        foreach($regPersons as $person) {

            $personView = $this->regPersonView;

            $personView->setPerson($person);

            $html .= $this->renderRegPerson($project, $personView, $displayKey);

        }
        $html .= <<<EOD
</table>
EOD;

        return $html;
    }
    private function renderRegPerson(ProjectInterface $project, RegPersonViewDecorator $personView, ?string $displayKey) : string
    {
        $html = null;

        switch($displayKey) {

            case 'Plans':
                $html .= <<<EOD
<tr id="project-person-{$personView->regPersonId}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($project, $personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderPlansInfo       ($personView)}</td>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
<tr id="project-person-{$personView->regPersonId}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($project, $personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderUserInfo        ($personView)}</td>
</tr>
EOD;
                break;
            
            case 'Avail':
            case 'Availability':
                $html .= <<<EOD
<tr id="project-person-{$personView->regPersonId}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($project, $personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderAvailInfo       ($personView)}</td>
</tr>
EOD;
                break;
        }
        return $html;
    }
    private function renderRegistrationInfo(RegPersonViewDecorator $personView) : string
    {
        $href = $this->generateUrl('reg_person_admin_update',['regPersonId' => $personView->regPersonId]);

        $gage = $personView->gender . $personView->age;
        return <<<EOD
<table>
  <tr><td>Name  </td><td  class="admin-listing"><a href="{$href}">{$this->escape($personView->name)}</a></td></tr>
  <tr><td>Email  </td><td class="admin-listing"><a href="mailto:{$this->escape($personView->email)}">{$this->escape($personView->email)}</a></td></tr>
  <tr><td>Phone </td><td>{$this->escape($personView->phone)} </td></tr>
  <tr><td>G Age</td><td> {$this->escape($gage)}</td></tr>
  <tr><td>Shirt </td><td>{$this->escape($personView->shirtSize)}</td></tr>
</table>
EOD;

    }
    // TODO Pull ayso name,email,phone if available
    private function renderAysoInfo(ProjectInterface $project, RegPersonViewDecorator $personView) : string
    {
        $regYearProject = $project->regYear;
        $fedId = $personView->fedId;
        $aysoId = $fedId ? "<a href='{$this->certURL}{$fedId}' target='_blank'>{$fedId}</a>" : '';
        return <<<EOD
<table>
  <tr>
    <td >Name</td>
    <td  class="admin-listing">{$this->escape($personView->name)}</td>
  </tr><tr>
  <tr>
    <td class="admin-listing">Email</td>
    <td><a href="mailto:{$this->escape($personView->email)}">{$this->escape($personView->email)}</a></td>
  </tr><tr>
  <tr>
    <td>AYSO ID</td>
    <td>{$aysoId}</td>
  </tr><tr>
    <td>S/A/R/St</td>
    <td class="{$personView->getOrgKeyClass()}">{$personView->orgKey}</td>
  </tr><tr>
    <td>Mem Year</td>
    <td class="{$personView->getRegYearClass($regYearProject)}">{$personView->getRegYear($regYearProject)}</td>
  </tr>
</table>
EOD;
    }
    private function renderPlansInfo(RegPersonViewDecorator $personView) : string
    {
        $notesUser = $personView->notesUser ?: '';
        if (strlen($notesUser) > 75) {
            $notesUser = substr($notesUser, 0, 75) . '...';
        }
        $notesUser = $this->escape($notesUser);

        return <<<EOD
<table>
  <tr><td>Will Referee  </td><td>{$personView->willReferee}  </td></tr>
  <tr><td>Will Volunteer</td><td>{$personView->willVolunteer}</td></tr>
  <tr><td>Will Coach    </td><td>{$personView->willCoach}    </td></tr>
  <tr><td colspan="2" style="max-width: 150px; ">{$notesUser}</td></tr>
</table>
EOD;

    }
    private function renderAvailInfo(RegPersonViewDecorator $personView) : string
    {

        return <<<EOD
<table>
  <tr><td>Avail Tuesday  </td><td>{$personView->availTue}     </td></tr>
  <tr><td>Avail Wednesday</td><td>{$personView->availWed}     </td></tr>
  <tr><td>Avail Thursday </td><td>{$personView->availThu}     </td></tr>
  <tr><td>Avail Friday   </td><td>{$personView->availFri}     </td></tr>
  <tr><td>Avail Sat Morn </td><td>{$personView->availSatMorn} </td></tr>
  <tr><td>Avail Sat After</td><td>{$personView->availSatAfter}</td></tr>
  <tr><td>Avail Sun Morn </td><td>{$personView->availSunMorn} </td></tr>
  <tr><td>Avail Sun After</td><td>{$personView->availSunAfter}</td></tr>
</table>
EOD;

    }
    private function renderRoles(RegPersonViewDecorator $personView) : string
    {
        $html = <<<EOD
<table>
EOD;
        foreach($personView->getRoles() as $role) {

            $html .= <<<EOD
<tr><td class="{$personView->getRoleClass($role)}">{$role->role}</td></tr>   
EOD;
        }
        foreach($personView->getCerts() as $cert) {

            $certKey = $cert->role;

            $html .= <<<EOD
<tr><td class="{$personView->getCertClass($certKey)}">{$certKey}: {$personView->getCertBadge($certKey)}</td></tr>   
EOD;
        }
        $html .= <<<EOD
</table>
EOD;
        return $html;
    }
    private function renderUserInfo(RegPersonViewDecorator $personView) : string
    {
        $user = $this->userFinder->find($personView->personId);
        
        if ($user === null) return '';

        $enabled = $user->isEnabled ? 'Yes' : 'NO';
        
        $roles = implode(',',$user->getRoles());

        return <<<EOD
<table>
  <tr><td>Name   </td><td class="admin-listing">{$this->escape($user->name)}    </td></tr>
  <tr><td>Email  </td><td class="admin-listing"><a href="mailto:{$this->escape($user->email)}">{$this->escape($user->email)}</a></td></tr>
  <tr><td>User   </td><td>{$this->escape($user->username)}</td></tr>
  <tr><td>Enabled</td><td>{$enabled}</td></tr>
  <tr><td>Roles  </td><td>{$roles}  </td></tr>
</table>
EOD;

    }
}
