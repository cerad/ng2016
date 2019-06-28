<?php

namespace AppBundle\Action\Project\Person;

use AysoBundle\DataTransformer\RegionToSarTransformer;
use AysoBundle\DataTransformer\VolunteerKeyTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Project\Person\ViewTransformer\WillRefereeTransformer;

class ProjectPersonViewDecorator
{
    /** @var  ProjectPerson */
    public $person;
    public $regYear;
    private $orgKey;

    private $phoneTransformer;
    private $fedKeyTransformer;
    private $orgKeyTransformer;
    private $willRefereeTransformer;
    private $appProjectRegYear;

    public function __construct(
        PhoneTransformer $phoneTransformer,
        VolunteerKeyTransformer $fedKeyTransformer,
        RegionToSarTransformer $orgKeyTransformer,
        WillRefereeTransformer $willRefereeTransformer,
        array $app_project
    ) {
        $this->phoneTransformer = $phoneTransformer;
        $this->fedKeyTransformer = $fedKeyTransformer;
        $this->orgKeyTransformer = $orgKeyTransformer;
        $this->willRefereeTransformer = $willRefereeTransformer;
        $this->appProjectRegYear = $app_project['info']['regYear'];
    }

    public function setProjectPerson(ProjectPerson $person)
    {
        $this->person = $person;
        $this->regYear = $person->regYear;
        $this->orgKey = $person->orgKey;
    }

    public function getKey()
    {
        return $this->person->getKey();
    }

    public function getRoles()
    {
        return $this->person->getRoles();
    }

    public function getCerts()
    {
        return $this->person->getCerts();
    }

    //private $infoClass    = 'bg-info';
    public $dangerClass = 'bg-danger';
    public $warningClass = 'bg-warning';
    public $successClass = 'bg-success';

    //public $infoStyle    = 'background-color: #d9edf7';
    public $dangerStyle = 'background-color: #f2dede';
    public $warningStyle = 'background-color: #fcf8e3';
    public $successStyle = 'background-color: #dff0d8';

    // Don't really like having to use methods here
    public function getRegYearClass($regYearProject)
    {
        return $this->regYear >= $regYearProject ? $this->successClass : $this->dangerClass;
    }

    public function getRegYearStyle($regYearProject)
    {
        return $this->regYear >= $regYearProject? $this->successStyle : $this->dangerStyle;
    }

    public function getRegYear($regYearProject)
    {
        return $this->regYear >= $regYearProject ? $this->regYear : $this->regYear.' ***';
    }

    public function getOrgKeyClass()
    {
        $sar = $this->orgKey;
        if (empty($sar)) {
            return $this->dangerClass;
        }
        $sarClass = ($sar && substr($sar, 0, 1) == 'A') ? empty($sar) ? $this->dangerStyle : $this->successStyle : $this->warningClass;

        return $sarClass;
    }

    public function getOrgKeyStyle()
    {
        $sar = $this->orgKey;
        return ($sar && substr($sar, 0, 1) == 'A') ? empty($sar) ? $this->dangerStyle : $this->successStyle :
            $this->warningStyle;
    }

    public function getCertClass($certKey)
    {
        $cert = $this->person->getCert($certKey);
        if (is_null($cert) || is_null($cert->badgeDate) || $cert->badgeDate == '0000-00-00') {
            return $this->dangerClass;
        };

        return $this->isCertValid($this->person->getCert($certKey)) ? $this->successClass : $this->dangerClass;
    }

    public function getCertStyle($certKey)
    {
        $cert = $this->person->getCert($certKey);
        if (!$this->isCertValid($cert)) {
            return $this->dangerClass;
        };

        return $this->isCertValid($this->person->getCert($certKey)) ? $this->successStyle : $this->dangerStyle;
    }

    public function getConflictedCertBadge()
    {
        $certs = $this->getCerts();
        if (empty($certs)) {
            return null;
        }
        $cert = $this->person->getCert('CERT_REFEREE');

        if (!empty($cert) && ($cert->badgeUser != $cert->badge) && !empty($cert->badgeUser)) {
//            var_dump($cert);
//            var_dump($cert->badgeUser);
//            var_dump($cert->badge);
            return true;
        } else {
            return false;
        }
    }

    public function getCertBadge($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        $cert = $this->person->getCert($certKey);
        if ($certKey !== 'CERT_REFEREE') {
            $isCert = $this->isCertValid($cert) AND $cert->verified ;

            return $isCert ? 'Yes': 'No';
        }
        if ((!$cert->badgeUser) || $cert->badge === $cert->badgeUser) {

            return $cert->badge;
        }

        return $cert->badge.'/'.$cert->badgeUser;
    }

    public function hasCertIssues()
    {
        $certs = $this->getCerts();
        if (empty($certs)) {
            return true;
        }

        foreach ($certs as $cert) {
            if (!$this->isCertValid($cert)) {
                return true;
            }
        }

        return false;
    }

    protected function isCertValid($cert)
    {
        if (empty($cert)) {
            return true;
        }

        $isValid = !(is_null($cert) || is_null($cert->badgeDate) || $cert->badgeDate == '0000-00-00');

        return $isValid;
    }

    public function isCurrentMY($regYearProject)
    {
        $regYearCurrent = $regYearProject >= $this->appProjectRegYear;

        return $regYearCurrent;
    }

    public function hasRoleReferee()
    {
        $role = $this->person->getRole('ROLE_REFEREE');

        return !is_null($role);
    }

    public function hasRoleVolunteer()
    {
        $role = $this->person->getRole('ROLE_VOLUNTEER');

        return !is_null($role);
    }

    /**
     * @param $role
     * @return string
     * @throws \Exception
     */
    public function getRoleClass($role)
    {
        $certIssue = $this->hasCertIssues() || !$this->isCurrentMY($this->regYear);

        switch ($role->role) {
            case 'ROLE_REFEREE':
                $roleClass = $this->__get('approvedRef') ? !$certIssue &&
                    $this->hasRoleReferee() ? $this->successClass : $this->warningClass : $this->dangerClass;
                break;
            case 'ROLE_VOLUNTEER':
                $roleClass = $this->__get('approvedVol') ? $this->__get('safeHavenCertified') != 'Yes' ?
                    $this->warningClass :
                    $this->successClass : $this->dangerClass;
                break;
            default:
                $roleClass = !$certIssue ? $this->successClass :
                    $this->dangerClass;
        }

        return $roleClass;
    }

    public function getRoleStyle($role)
    {
        if ($role->approved) {
            return $this->successStyle;
        }

        return (!$this->hasCertIssues()) ? $this->successStyle : $this->dangerStyle;
    }

    /**
     * @param $name
     * @return ProjectPerson|bool|mixed|string|string[]|null
     * @throws \Exception
     */
    public function __get($name)
    {
        $person = $this->person;

        switch ($name) {

            case 'approved':
                $approved = $this->__get('approvedRef') && $this->__get('approvedVol');

                return $approved;
                break;

            case 'approvedRef':
                $role = $person->getRole('ROLE_REFEREE');
                if(empty($role)) {
                    return true;
                }
                return $role['approved'];

            case 'approvedVol':
                $role = $person->getRole('ROLE_VOLUNTEER');
                if(empty($role)) {
                    return true;
                }
                return $role['approved'];

            case 'verifiedRef':
                $role = $person->getRole('ROLE_REFEREE');
                if(empty($role)) {
                    return '';
                }

                return $role ? ($role['verified'] ? 'Yes' : '') : null;

            case 'verifiedVol':
                $role = $person->getRole('ROLE_VOLUNTEER');
                if(empty($role)) {
                    return '';
                }

                return $role ? ($role['verified'] ? 'Yes' : '') : null;

            case 'phone':
                return $this->phoneTransformer->transform($person->phone);

            case 'fedId':
            case 'fedKey':
                $fedKey = $this->fedKeyTransformer->transform($person->fedKey);
                return $fedKey;

            case 'sar':
            case 'orgKey':
                $sar = $this->orgKeyTransformer->transform($person->orgKey);
                if((int)$sar == 0) $sar = '';
                return $sar;

            case 'personKey':
                return $person->personKey;

            case 'refereeBadge':
                $cert = $person->getCert('CERT_REFEREE');

                return $this->isCertValid($cert) ? $cert->badge : null;

            case 'refereeBadgeUser':
                $cert = $person->getCert('CERT_REFEREE');

                return $this->isCertValid($cert) ? $cert->badgeUser : null;

            case 'safeHavenCertified':
                $cert = $person->getCert('CERT_SAFE_HAVEN');

                return $this->isCertValid($cert) ? 'Yes' : 'No';

            case 'concussionAware':
            case 'concussionTrained':
            case 'concussionCertified':
                $cert = $person->getCert('CERT_CONCUSSION');

                return $this->isCertValid($cert) ? 'Yes' : 'No';

            case 'backgroundChecked':
            case 'floridaResident':
                $cert = $person->getCert('CERT_BACKGROUND_CHECK');
                return $this->isCertValid($cert) ? 'Yes' : 'No';

            case 'willCoach':
            case 'willAttend':
            case 'willReferee':
            case 'willVolunteer':
                $will = isset($person->plans[$name]) ? $person->plans[$name] : null;

                return ucfirst(strtolower($will));

            case 'willRefereeBadge':
                $willRefereeTransformer = $this->willRefereeTransformer;

                return $willRefereeTransformer($person);

            case 'availTue':
            case 'availWed':
            case 'availThu':
            case 'availFri':
            case 'availSatMorn':
            case 'availSatAfter':
            case 'availSunMorn':
            case 'availSunAfter':
                $will = isset($person->avail[$name]) ? $person->avail[$name] : null;

                return ucfirst(strtolower($will));

            case 'shirtSize':
                $size = strtolower($person->shirtSize);
                switch ($size) {
                    case 'youths':
                        return 'Youth S';
                    case 'youthm':
                        return 'Youth M';
                    case 'youthl':
                        return 'Youth L';
                    case 'adults':
                        return 'Adult S';
                    case 'adultm':
                        return 'Adult M';
                    case 'adultl':
                        return 'Adult L';
                    case 'adultlx':
                        return 'Adult XL';
                    case 'adultlxx':
                        return 'Adult XXL';
                    case 'adultlxxx':
                        return 'Adult XXXL';
                }

                return 'na';

            case 'person':
                return $person;

            case 'notes':
                return $person->notes;

            case 'notesUser':
                return $person->notesUser;

        }

        return $person->$name;
    }
}
