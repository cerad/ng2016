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
                $role = $person->getRole('ROLE_REFEREE');
                return $role ? $role->badge : null;

            case 'safeHavenCertified':
                $role = $person->getRole('ROLE_SAFE_HAVEN');
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
                $role = $person->getRole('ROLE_CONCUSSION');
                if (!$role) return null;
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