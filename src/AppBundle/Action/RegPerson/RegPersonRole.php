<?php
namespace AppBundle\Action\RegPerson;

use AppBundle\Common\ItemFactoryTrait;

class RegPersonRole
{
    use ItemFactoryTrait;

    public $projectId;
    public $personId;
    
    public $role;
    public $roleDate;

    public $badge;
    public $badgeUser;
    public $badgeDate;
    public $badgeExpires;

    public $active   = true;
    public $approved = false;
    public $verified = false;
    public $ready    = true;

    public $misc;
    public $notes;

    protected $keys = [

        'projectId' => 'ProjectId',
        'personId'  => 'PersonId',
        
        'role'     => 'Role', // Required
        'roleDate' => 'date',
        
        'badge'        => 'Badge', // Probably should be required?
        'badgeUser'    => 'Badge',
        'badgeDate'    => 'date',
        'badgeExpires' => 'date',
        
        'active'   => 'boolean',
        'approved' => 'boolean',
        'verified' => 'boolean',
        'ready'    => 'boolean',

        'misc'  => 'boolean',
        'notes' => 'boolean',
    ];
    /**
     * @param  array $data
     * @return RegPerson
     */
    static public function createFromArray($data)
    {
        $item = new self();

        $item->loadFromArray($data);

        return $item;
    }
}