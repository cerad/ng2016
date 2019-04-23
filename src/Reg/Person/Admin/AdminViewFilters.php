<?php declare(strict_types=1);

namespace Zayso\Reg\Person\Admin;

use Zayso\Reg\Person\RegPersonFinder;
use Zayso\Reg\Person\RegPersons;
use Zayso\Reg\Person\RegPersonViewDecorator;

class AdminViewFilters
{
    private $regPersonFinder;
    private $regPersonView;
    
    public function __construct(
        RegPersonFinder        $regPersonFinder,
        RegPersonViewDecorator $regPersonView
    ) {
        $this->regPersonFinder = $regPersonFinder;
        $this->regPersonView   = $regPersonView;
    }
    public function filterByReport(RegPersons $regPersons, ?string $reportKey = null) : RegPersons
    {
        $listPersons = new RegPersons();
        
        $yesMaybe = ['Yes', 'Maybe'];

        $personView = $this->regPersonView;

        foreach ($regPersons as $person) {

            $personView->setPerson($person);

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
                    // TODO Project oriented background check requirement
                /*
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
                    
                            //$concCert->active = true;
                    
                            $person->addCert($concCert);

                            //$this->projectPersonRepository->save($person);
                        }

                        $listPersons[] = $person;                        
                    }
                    
                    break;*/
                default:
                    $listPersons[] = $person;
                    break;
            }
        }
        return $listPersons;
    }
    private function hasIssues(RegPersonViewDecorator $personView) : bool
    {
        $issues = false;
        $certs = $personView->getCerts();
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
