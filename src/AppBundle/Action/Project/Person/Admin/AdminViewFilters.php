<?php
namespace AppBundle\Action\Project\Person\Admin;

use AppBundle\Action\Project\Person\ProjectPersonViewDecorator;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

class AdminViewFilters
{
    /** var ProjectPersonViewDecorator **/
    private $projectPersonViewDecorator;
    
    /** var ProjectPersonRepositoryV2 **/
    private $projectPersonRepository;

    /** var string $regYearProject */
    private $regYearProject;

    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        ProjectPersonRepositoryV2 $projectPersonRepository,
        array $appProject
    )
    {
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
        $this->projectPersonRepository = $projectPersonRepository;
        $this->regYearProject = $appProject['info']['regYear'];
    }
    public function getPersonListByReport(array $projectPersons, $reportKey = null)
    {
        $listPersons = [];
        
        $yesMaybe = ['Yes', 'Maybe'];

        $personView = $this->projectPersonViewDecorator;

        foreach ($projectPersons as $person) {

            $personView->setProjectPerson($person);

            switch ($reportKey) {
                case 'All':
                case null:
                    $listPersons[] = $person;
                    break;
                case 'Referees':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        $listPersons[] = $person;
                    }
                    break;
                case 'Volunteers':
                    if (in_array($personView->willVolunteer, $yesMaybe)) {
                        $listPersons[] = $person;
                    }
                    break;
                case 'RefIssues':
                case 'Referees with Issues':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        if ( $this->hasIssues($personView) ) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'VolIssues':
                case 'Volunteers with Issues':
                    if (in_array($personView->willVolunteer, $yesMaybe)) {
                        if ( $personView->hasCertIssues() ) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'Unapproved':
                    if (isset($person['roles']['ROLE_REFEREE'])) {
                        if (!$personView->approved AND !$personView->hasCertIssues()) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
//                case 'FL':
//                case 'FL Residents':
//                    //get the state
//                    $stateArr = explode('/',$personView->orgKey);
//                    //check CERT_BACKGROUND_CHECK
//                    if (strpos($personView->orgKey, '/FL')) {
//                        // Background check for FL residents
//                        if (!$person->hasCert('CERT_BACKGROUND_CHECK')) {
//                            $certKey = 'CERT_BACKGROUND_CHECK';
//                            $concCert = $person->getCert($certKey,true);
//
//                            $concCert->active = true;
//
//                            $person->addCert($concCert);
//
//                            $this->projectPersonRepository->save($person);
//                        }
//
//                        $listPersons[] = $person;
//                    }
//
//                    break;
                default:
                    $listPersons[] = $person;
                    break;
            }
        }
        
        return $listPersons;
    }
    private function hasIssues(ProjectPersonViewDecorator $personView)
    {
        if($personView->hasRegistrationIssue($this->regYearProject)) {

            return true;
        }
        
        $certs = $personView->getCerts();
        $issues = false;
        foreach($certs as $cert) {
            $issues |= !(bool)$cert->verified;
        }

        //$roles = $personView->getRoles();
        //foreach($roles as $role) {
        //    $issues |= !(bool)$role->verified;
        //}

        return boolval($issues);
    }
    
}
