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
    
    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        ProjectPersonRepositoryV2 $projectPersonRepository
    )
    {
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
        $this->projectPersonRepository = $projectPersonRepository;
    }
    public function getPersonListByReport(array $projectPersons, $reportKey = null)
    {
        $listPersons = [];

        $personView = $this->projectPersonViewDecorator;

        foreach ($projectPersons as $person) {

            $personView->setProjectPerson($person);

            switch ($reportKey) {
                case 'All':
                case null:
                    $listPersons[] = $person;
                    break;
                case 'Referees':
                    if ($personView->willReferee == 'Yes') {
                        $listPersons[] = $person;
                    }
                    break;
                case 'Volunteers':
                    if ($personView->willVolunteer == 'Yes') {
                        $listPersons[] = $person;
                    }
                    break;
                case 'RefIssues':
                case 'Referees with Issues':
                    if ($personView->willReferee == 'Yes') {
                        if ( $this->hasIssues($personView) ) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'VolIssues':
                case 'Volunteers with Issues':
                    if ($personView->willVolunteer == 'Yes') {
                        if ( $this->hasIssues($personView) ) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'Unapproved':
                    if (isset($person['roles']['ROLE_REFEREE'])) {
                        if (!$personView->approved AND $personView->verified) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'FL':
                case 'FL Residents':
                    //get the state
                    $stateArr = explode('/',$personView->orgKey);
                    //check CERT_BACKGROUND_CHECK
                    if (strpos($personView->orgKey, '/FL')) {
                        // Background check for FL residents
                        if (!$person->hasCert('CERT_BACKGROUND_CHECK')) {
                            $certKey = 'CERT_BACKGROUND_CHECK';
                            $concCert = $person->getCert($certKey,true);
                    
                            $concCert->active = true;
                    
                            $person->addCert($concCert);

                            $this->projectPersonRepository->save($person);
                        }

                        $listPersons[] = $person;                        
                    }
                    
                    break;
                default:
                    $listPersons[] = $person;
                    break;
            }
        }
        
        return $listPersons;
    }
    private function hasIssues(ProjectPersonViewDecorator $personView)
    {
        $issues = false;
        
        $certs = $personView->getCerts();
        $issues = false;
        foreach($certs as $cert) {
            $issues |= !(bool)$cert->verified;
        }

        return boolval($issues);
    }
    
}