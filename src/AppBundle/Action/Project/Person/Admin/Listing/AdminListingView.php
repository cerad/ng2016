<?php
namespace AppBundle\Action\Project\Person\Admin\Listing;

use AppBundle\Action\AbstractView2;

use AppBundle\Action\Project\Person\ProjectPerson;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\User\ProjectUserRepository;
use AppBundle\Action\Project\Person\Admin\AdminViewFilters;

use Symfony\Component\HttpFoundation\Request;

class AdminListingView extends AbstractView2
{
    private $searchForm;
    private $displayKey;
    private $reportKey;

    /** @var  ProjectPerson[] */
    private $projectPersons;
    private $projectPersonsCount;

    private $projectUserRepository;

    private $projectPersonRepository;

    private $projectPersonViewDecorator;
    
    private $adminViewFilters;

    private $regYearProject;

    public function __construct(
        ProjectPersonRepositoryV2  $projectPersonRepository,
        ProjectUserRepository      $projectUserRepository,
        AdminListingSearchForm     $searchForm,
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        AdminViewFilters           $adminViewFilters
    )
    {
        $this->searchForm = $searchForm;
        $this->projectUserRepository        = $projectUserRepository;
        $this->projectPersonRepository      = $projectPersonRepository;
        $this->projectPersonViewDecorator   = $projectPersonViewDecorator;
        $this->adminViewFilters             = $adminViewFilters;
    }
    public function __invoke(Request $request)
    {
        $this->displayKey     = $request->attributes->get('displayKey');
        $this->reportKey     = $request->attributes->get('reportKey');
        $this->projectPersons = $request->attributes->get('projectPersons');
        $this->regYearProject = $this->getCurrentProjectInfo()['regYear'];

        $listPersons = $this->adminViewFilters->getPersonListByReport($this->projectPersons, $this->regYearProject, $this->reportKey);
        
        $this->projectPersons = $listPersons;

        $this->projectPersonsCount = count($this->projectPersons);

        return $this->newResponse($this->render());
    }
    private function render()
    {
        $content = <<<EOD
<legend>Person Listing, Count: {$this->projectPersonsCount}</legend>
{$this->searchForm->render()}
<br/>
{$this->renderProjectPersons()}
EOD;
        return $this->renderBaseTemplate($content);
    }
    private function renderProjectPersons()
    {
        $html = <<<EOD
<table class='table'>
<tr>
EOD;

        switch($this->displayKey) {

            case 'Plans':
                $html .= <<<EOD
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Plans</th>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
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
  <th>Registration Information</th>
  <th>AYSO Information</th>
  <th>Roles / Certs</th>
  <th>Availability</th>
</tr>
EOD;
                break;
        }

        foreach($this->projectPersons as $person) {
            // Should this be a private variable to be consistent?
            $personView = $this->projectPersonViewDecorator;

            $personView->setProjectPerson($person);                    

            $html .= $this->renderProjectPerson($personView);

        }
        $html .= <<<EOD
</table>
EOD;

        return $html;
    }
    private function renderProjectPerson(ProjectPersonViewDecorator $personView)
    {
        $html = null;

        switch($this->displayKey) {

            case 'Plans':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderPlansInfo       ($personView)}</td>
</tr>
EOD;
                break;

            case 'User':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderUserInfo        ($personView)}</td>
</tr>
EOD;
                break;
            
            case 'Avail':
            case 'Availability':
                $html .= <<<EOD
<tr id="project-person-{$personView->getKey()}">
  <td>{$this->renderRegistrationInfo($personView)}</td>
  <td>{$this->renderAysoInfo        ($personView)}</td>
  <td>{$this->renderRoles           ($personView)}</td>
  <td>{$this->renderAvailInfo       ($personView)}</td>
</tr>
EOD;
                break;
        }
        return $html;
    }
    private function renderRegistrationInfo(ProjectPersonViewDecorator $personView)
    {
        $href = $this->generateUrl('project_person_admin_update',['projectPersonKey' => $personView->getKey()]);

        switch ($personView->gender) {
            case 'M':
                $gender = 'Male';
                break;
            case 'F':
                $gender = 'Female';
                break;
            default:
                $gender = '';
        }
        return <<<EOD
<table>
  <tr><td>Name</td><td  class="admin-listing"><a href="{$href}">{$this->escape($personView->name)}</a></td></tr>
  <tr><td>Email</td><td class="admin-listing"><a href="mailto:{$this->escape($personView->email)}">{$this->escape($personView->email)}</a></td></tr>
  <tr><td>Phone</td><td>{$this->escape($personView->phone)} </td></tr>
  <tr><td>Gender</td><td> {$this->escape($gender)}</td></tr>
  <tr><td>Age</td><td> {$this->escape($personView->age)}</td></tr>
  <tr><td>Shirt </td><td>{$this->escape($personView->shirtSize)}</td></tr>
  <tr><td>Adult Ref Exp</td><td>{$personView->adultExp} yrs</td></tr>
</table>
EOD;

    }
    // TODO Pull ayso name,email,phone if available
    private function renderAysoInfo(ProjectPersonViewDecorator $personView)
    {
        return <<<EOD
<table>
  <tr>
    <td >Name</td>
    <td  class="admin-listing">{$personView->name}</td>
  </tr><tr>
  <tr>
    <td class="admin-listing">Email</td>
    <td><a href="mailto:{$this->escape($personView->email)}">{$this->escape($personView->email)}</a></td>
  </tr><tr>
  <tr>
    <td>AYSO ID</td>
    <td>{$personView->fedId}</td>
  </tr><tr>
    <td>S/A/R/St</td>
    <td class="{$personView->getOrgKeyClass()}">{$personView->orgKey}</td>
  </tr><tr>
    <td>Mem Year</td>
    <td class="{$personView->getRegYearClass($this->regYearProject)}">{$personView->getRegYear($this->regYearProject)}</td>
  </tr>
</table>
EOD;
    }
    private function renderPlansInfo(ProjectPersonViewDecorator $personView)
    {
        $notesUser = $personView->notesUser;
        if (strlen($notesUser) > 75) {
            $notesUser = substr($notesUser, 0, 75) . '...';
        }
        $notesUser = $this->escape($notesUser);

        return <<<EOD
<table>
  <tr><td>Will  Referee  </td><td>{$personView->willReferee}  </td></tr>
  <tr><td>Will  Volunteer</td><td>{$personView->willVolunteer}</td></tr>
  <tr><td>Will  Coach    </td><td>{$personView->willCoach}    </td></tr>
  <tr><td colspan="2" style="max-width: 150px; ">{$notesUser}</td></tr>
</table>
EOD;

    }
    private function renderAvailInfo(ProjectPersonViewDecorator $personView)
    {

        return <<<EOD
<table>
<!--  <tr><td>Avail Wednesday</td><td>{$personView->availWed}     </td></tr> -->
<!--    <tr><td>Avail Thursday </td><td>{$personView->availThu}     </td></tr>  -->
  <tr><td>Avail Friday   </td><td>{$personView->availFri}     </td></tr>
  <tr><td>Avail Sat Morn </td><td>{$personView->availSatMorn} </td></tr>
  <tr><td>Avail Sat After</td><td>{$personView->availSatAfter}</td></tr>
  <tr><td>Avail Sun Morn </td><td>{$personView->availSunMorn} </td></tr>
  <tr><td>Avail Sun After</td><td>{$personView->availSunAfter}</td></tr>
</table>
EOD;

    }
    private function renderRoles(ProjectPersonViewDecorator $personView)
    {
        $regYearProject = $this->getCurrentProjectInfo()['regYear'];

        $html = <<<EOD
<table>
EOD;
        foreach($personView->getRoles() as $role) {
            $html .= <<<EOD
<tr><td class="{$personView->getRoleClass($role, $regYearProject)}">{$role->role}</td></tr>   
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
    private function renderUserInfo(ProjectPersonViewDecorator $personView)
    {
        $user = $this->projectUserRepository->find($personView->personKey);
        
        if (empty($user)) {
            return null;
        }

        $enabled = $user['enabled'] ? 'Yes' : 'NO';
        
        $roles = implode(',',$user['roles']);

        return <<<EOD
<table>
  <tr><td>Name   </td><td class="admin-listing">{$this->escape($user['name'])}    </td></tr>
  <tr><td>Email  </td><td class="admin-listing"><a href="mailto:{$this->escape($user['email'])}">{$this->escape($user['email'])}</a></td></tr>
  <tr><td>User   </td><td>{$this->escape($user['username'])}</td></tr>
  <tr><td>Enabled</td><td>{$enabled}</td></tr>
  <tr><td>Roles  </td><td>{$roles}  </td></tr>
</table>
EOD;

    }
}
