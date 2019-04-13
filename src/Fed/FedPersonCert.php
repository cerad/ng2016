<?php declare(strict_types=1);

namespace Zayso\Fed;

use InvalidArgumentException;
use Zayso\Common\Traits\SetterTrait;

/**
 * @property-read string $role
 * @property-read string $roleDate
 * @property-read string $badge
 * @property-read string $badgeDate
 * @property-read int    $sort
 * @property-read bool   $isVerified
 *
 * TODO Add sport to generalize
 */
class FedPersonCert
{
    use SetterTrait;

    public $role;
    public $roleDate;
    public $badge;
    public $badgeDate;
    public $sort;

    public function __construct(string $role, string $roleDate, string $badge, string $badgeDate, int $sort)
    {
        $this->role      = $role;
        $this->roleDate  = $roleDate;
        $this->badge     = $badge;
        $this->badgeDate = $badgeDate;
        $this->sort      = $sort;
    }
    public function withRoleDate(string $roleDate) : FedPersonCert
    {
        return new FedPersonCert($this->role,$roleDate,$this->badge,$this->badgeDate,$this->sort);
    }
    public function __get($name)
    {
        switch($name) {
            case 'isVerified':
                return isset($this->date) ? true : false;
        }
        throw new InvalidArgumentException('FedPersonCert::' . $name);
    }
}