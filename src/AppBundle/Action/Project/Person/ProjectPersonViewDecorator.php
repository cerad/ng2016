<?php
namespace AppBundle\Action\Project\Person;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\VolunteerKeyTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Project\Person\ViewTransformer\WillRefereeTransformer;

class ProjectPersonViewDecorator
{
    /** @var  ProjectPerson */
    private $person;

    private $phoneTransformer;
    private $fedKeyTransformer;
    private $orgKeyTransformer;
    
    /** @var WillRefereeTransformer */
    private $willRefereeTransformer;

    public function __construct(
        PhoneTransformer        $phoneTransformer,
        VolunteerKeyTransformer $fedKeyTransformer,
        RegionToSarTransformer  $orgKeyTransformer,
        WillRefereeTransformer  $willRefereeTransformer
    )
    {
        $this->phoneTransformer  = $phoneTransformer;
        $this->fedKeyTransformer = $fedKeyTransformer;
        $this->orgKeyTransformer = $orgKeyTransformer;

        $this->willRefereeTransformer = $willRefereeTransformer;
    }
    public function setProjectPerson(ProjectPerson $person)
    {
        $this->person = $person;
    }
    public function getKey()
    {
        return $this->person->getKey();
    }
    public function getRoles() {
        return $this->person->getRoles();
    }
    public function getCerts() {
        return $this->person->getCerts();
    }
    //private $infoClass    = 'bg-info';
    private $dangerClass  = 'bg-danger';
    private $warningClass = 'bg-warning';
    private $successClass = 'bg-success';

    // Don't really like having to use methods here
    public function getRegYearClass($regYearProject)
    {
        return $this->regYear >= $regYearProject ? $this->successClass : $this->dangerClass;
    }
    public function getRegYear($regYearProject)
    {
        return $this->regYear >= $regYearProject ? $this->regYear : $this->regYear . ' ***';
    }
    public function getOrgKeyClass()
    {
        $sar = $this->orgKey;
        return ($sar && substr($sar,0,1) !== 'A') ? $this->successClass : $this->dangerClass;
    }
    public function getCertClass($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        return $this->person->getCert($certKey)->verified ? $this->successClass : $this->dangerClass;
    }
    public function getCertBadge($certKey)
    {
        if (!$this->person->hasCert($certKey)) {
            return null;
        };
        $cert = $this->person->getCert($certKey);
        $suffix = $cert->verified ? null : ' ***';
        if ($certKey !== 'CERT_REFEREE') {
            return $cert->verified ? 'Yes' : 'No' . $suffix;
        }    
        if ((!$cert->badgeUser) || $cert->badge === $cert->badgeUser) {
            return $cert->badge . $suffix;
        }
        return $cert->badge . '/' . $cert->badgeUser . $suffix;
    }
    public function getRoleClass($role)
    {
        if ($role->approved) {
            return $this->successClass;
        } 
        return $role->verified ? $this->warningClass : $this->dangerClass;
    }
public function __get($name)
    {
        $person = $this->person;
        
        switch($name) {
            
            case 'phone':  
                return $this->phoneTransformer->transform($person->phone);
            
            case 'fedId':
            case 'fedKey': 
                return $this->fedKeyTransformer->transform($person->fedKey);
            
            case 'sar':
            case 'orgKey': 
                return $this->orgKeyTransformer->transform($person->orgKey);

            case 'refereeBadge':
                $role = $person->getRole('CERT_REFEREE');
                return $role ? $role->badge : null;
            
            case 'refereeBadgeUser':
                $role = $person->getRole('CERT_REFEREE');
                return $role ? $role->badgeUser : null;

            case 'safeHavenCertified':
                $role = $person->getRole('CERT_SAFE_HAVEN');
                if (!$role) return null;
                switch(strtolower($role->badge)) {
                    case  null:
                    case 'no':
                    case 'none':
                        return null;
                }
                return 'Yes';

            case 'concussionAware':
            case 'concussionTrained':
            case 'concussionCertified':
                $role = $person->getRole('CERT_CONCUSSION');
                if (!$role) {
                    return null;
                }
                if ($role->verified) {
                    return 'Yes';
                }
                switch(strtolower($role->badge)) {
                    case  null:
                    case 'no':
                    case 'none':
                        return null;
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
                switch($size) {
                    case 'youths':    return 'Youth S';
                    case 'youthm':    return 'Youth M';
                    case 'youthl':    return 'Youth L';
                    case 'adults':    return 'Adult S';
                    case 'adultm':    return 'Adult M';
                    case 'adultl':    return 'Adult L';
                    case 'adultlx':   return 'Adult XL';
                    case 'adultlxx':  return 'Adult XXL';
                    case 'adultlxxx': return 'Adult XXXL';
                }
                return 'na';
        }
        return $person->$name;
    }
}