<?php declare(strict_types=1);

namespace App\Ayso;
/**
 * @property-read string $role
 * @property-read string $roleDate
 * @property-read string $badge
 * @property-read string $badgeDate
 * @property-read int    $sort
 */
class AysoVolCert
{
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
    public function withRoleDate(string $roleDate) : AysoVolCert
    {
        return new AysoVolCert($this->role,$roleDate,$this->badge,$this->badgeDate,$this->sort);
    }
}