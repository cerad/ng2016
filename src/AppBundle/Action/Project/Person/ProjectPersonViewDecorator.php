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
        if (is_null($this->sar)) {
            return $this->warningClass;
        }
        $sarClass = ($sar && substr($sar, 0, 1) == 'A') ? $this->successClass : $this->warningClass;

        return $sarClass;
    }

    public function getOrgKeyStyle()
    {
        $sar = $this->orgKey;

        return ($sar && substr($sar, 0, 1) == 'A') ? $this->successStyle : $this->warningStyle;
    }

    public function getCertClass($certKey)
    {
        $cert = $this->person->getCert($certKey);
        if (is_null($cert) || is_null($cert->badgeDate) || $cert->badgeDate == '0000-00-00') {
            return $this->dangerClass;
        };

        return $this->person->getCert($certKey)->verified ? $this->successClass : $this->dangerClass;
    }

    public function getCertStyle($certKey)
    {
        $cert = $this->person->getCert($certKey);
        if (is_null($cert) || is_null($cert->badgeDate || $cert->badgeDate == '0000-00-00')) {
            return $this->dangerClass;
        };

        return $this->person->getCert($certKey)->verified ? $this->successStyle : $this->dangerStyle;
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
        $suffix = $cert->verified ? null : ' ***';
        $isCert = false;
        if ($certKey !== 'CERT_REFEREE') {
            $isCert = !is_null($cert->badgeDate) && ($cert->badgeDate <> '0000-00-00') AND $cert->verified ;

            return $isCert ? 'Yes': 'No'.$suffix;
        }
        if ((!$cert->badgeUser) || $cert->badge === $cert->badgeUser) {

            return $cert->badge.$suffix;
        }

        return $cert->badge.'/'.$cert->badgeUser.$suffix;
    }

    public function hasCertIssues()
    {
        $certs = $this->getCerts();
        if (empty($certs)) {
            return true;
        }

        foreach ($certs as $cert) {
            if (is_null($cert) || is_null($cert->badgeDate) || $cert->badgeDate == '0000-00-00') {
                return true;
            }
        }

        return false;
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
                $roleClass = $this->__get('approvedVol') ? $certIssue ? $this->warningClass :
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

            case 'approvedRef':
                $role = $person->getRole('ROLE_REFEREE');
                return $role['approved'];

            case 'approvedVol':
                $role = $person->getRole('ROLE_VOLUNTEER');
                return $role['approved'];

            case 'verifiedRef':
                $role = $person->getRole('ROLE_REFEREE');

                return $role ? ($role['verified'] ? 'Yes' : '') : null;

            case 'verifiedVol':
                $role = $person->getRole('ROLE_VOLUNTEER');

                return $role ? ($role['verified'] ? 'Yes' : '') : null;

            case 'phone':
                return $this->phoneTransformer->transform($person->phone);

            case 'fedId':
            case 'fedKey':
                return $this->fedKeyTransformer->transform($person->fedKey);

            case 'sar':
            case 'orgKey':
                return $this->orgKeyTransformer->transform($person->orgKey);

            case 'personKey':
                return $person->personKey;

            case 'refereeBadge':
                $role = $person->getRole('CERT_REFEREE');

                return $role ? $role->badge : null;

            case 'refereeBadgeUser':
                $role = $person->getRole('CERT_REFEREE');

                return $role ? $role->badgeUser : null;

            case 'safeHavenCertified':
                $role = $person->getRole('CERT_SAFE_HAVEN');
                if (!$role) {
                    return null;
                }
                switch (strtolower($role->approved)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                    case '0000-00-00':
                        return 'No';
                }

                return 'Yes';

            case 'concussionAware':
            case 'concussionTrained':
            case 'concussionCertified':
                $role = $person->getRole('CERT_CONCUSSION');
                if (!$role) {
                    return null;
                }
                if ($role->approved) {
                    return 'Yes';
                }
                switch (strtolower($role->approved)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                    case '0000-00-00':
                        return 'No';
                }

                return 'Yes';

            case 'backgroundChecked':
            case 'floridaResident':
                $role = $person->getRole('CERT_BACKGROUND_CHECK');
                if (!$role) {
                    return 'nr';
                }
                if ($role->verified) {
                    return 'Yes';
                }
                switch (strtolower($role->approved)) {
                    case  null:
                    case 'no':
                    case 'none':
                    case '0':
                        return 'No';
                }

                return 'Yes';

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
