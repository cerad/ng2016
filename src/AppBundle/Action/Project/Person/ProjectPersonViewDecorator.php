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
            case 'phone':  return $this->phoneTransformer->transform($person->phone);
            case 'fedKey': return $this->fedKeyTransformer->transform($person->fedKey);
            case 'orgKey': return $this->orgKeyTransformer->transform($person->orgKey);

            case 'refereeBadge':
                $role = $person->getRole('ROLE_REFEREE');
                return $role ? $role->badge : null;

            case 'safeHavenCertified':
                $role = $person->getRole('ROLE_SAFE_HAVEN');
                if (!$role) return null;
                switch(strtolower($role->badge)) {
                    case null:
                    case 'none':
                        return null;
                }
                return 'Yes';

            case 'concussionTrained':
                $role = $person->getRole('ROLE_CONCUSSION');
                if (!$role) return null;
                switch(strtolower($role->badge)) {
                    case null:
                    case 'none':
                        return null;
                }
                return 'Yes';

        }
        return $person->$name;
    }
}