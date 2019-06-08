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

    /** string regYearProject */
    private $regYearProject;

    public function __construct(
        ProjectPersonViewDecorator $projectPersonViewDecorator,
        ProjectPersonRepositoryV2 $projectPersonRepository,
        array $appProject
    ) {
        $this->projectPersonViewDecorator = $projectPersonViewDecorator;
        $this->projectPersonRepository = $projectPersonRepository;
        $this->regYearProject = $appProject['info']['regYear'];
    }

    public function getPersonListByReport(array $projectPersons, $regYearProject, $reportKey = null)
    {
        $listPersons = [];

        $yes = ['Yes'];

        $yesMaybe = ['Yes', 'Maybe'];

        $personView = $this->projectPersonViewDecorator;

        foreach ($projectPersons as $person) {

            $personView->setProjectPerson($person);

            switch ($reportKey) {
                case 'All':
                case null:
                    $listPersons[] = $person;
                    break;
                case 'Available Referees':
                case 'AvailableReferees':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        if ($personView->hasRoleReferee()) {
                            if (!$personView->hasCertIssues() AND $personView->isCurrentMY($regYearProject)) {
                                $listPersons[] = $person;
                            }
                        }
                    }
                    break;
                case 'Available Volunteers':
                case 'AvailableVolunteers':
                    if (in_array($personView->willVolunteer, $yesMaybe)) {
                        if (!$personView->hasCertIssues() AND $personView->isCurrentMY($regYearProject)) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'RefIssues':
                case 'Referees with Issues':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        if ($personView->hasCertIssues() OR !$personView->isCurrentMY($regYearProject)) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'RefCertIssues':
                case 'Referees with Cert Issues':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        if ($personView->hasCertIssues()) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'RefCertConflicts':
                case 'Referees with Cert Conflicts':
                    if (in_array($personView->willReferee, $yesMaybe)) {
                        if ($personView->getConflictedCertBadge()) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'VolIssues':
                case 'Volunteers with Issues':
                    if (in_array($personView->willVolunteer, $yesMaybe)) {
                        if ($personView->hasCertIssues() OR !$personView->isCurrentMY($regYearProject)) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'Unapproved':
                    if ((isset($person['roles']['ROLE_REFEREE']) AND in_array($personView->willReferee, $yesMaybe)) OR
                        (isset($person['roles']['ROLE_VOLUNTEER']) AND in_array($personView->willVolunteer, $yesMaybe)
                        )) {
                        if (!$personView->approved
                            AND $personView->verified
                            AND !$personView->hasCertIssues()
                            AND $personView->isCurrentMY(
                                $regYearProject
                            )
                        ) {
                            $listPersons[] = $person;
                        }
                    }
                    break;
                case 'AdultRefs':
//                case 'Referees with Adult Experience':
//                    if (isset($person['roles']['ROLE_REFEREE'])) {
//                        if ($personView->hasAdultExp() /*AND $personView->approved */) {
//                            $listPersons[] = $person;
//                        }
//                    }
//                    break;
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

        uasort($listPersons, array($this, 'cmp'));

        return $listPersons;
    }

    private function hasIssues(
        ProjectPersonViewDecorator $personView
    ) {
        $certs = $personView->getCerts();
        $issues = false;
        foreach ($certs as $cert) {
            $issues |= !(bool)$cert->verified;
        }

        //$roles = $personView->getRoles();
        //foreach($roles as $role) {
        //    $issues |= !(bool)$role->verified;
        //}

        return boolval($issues);
    }


    public
    function cmp(
        $a,
        $b
    ) {
        $aName = explode(' ', ucwords($a->name));
        $firstA = $aName[0];
        $lastA = isset($aName[1]) ? $aName[1] : $aName[0];
        $bName = explode(' ', ucwords($b->name));
        $firstB = $bName[0];
        $lastB = isset($bName[1]) ? $bName[1] : $bName[0];

        if ($lastA == $lastB) {
            return ($firstA < $firstB) ? -1 : 1;
        }

        return ($lastA < $lastB) ? -1 : 1;
    }

}
