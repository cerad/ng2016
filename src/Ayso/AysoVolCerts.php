<?php
declare(strict_types=1);

namespace App\Ayso;

use \ArrayIterator;

class AysoVolCerts extends ArrayIterator
{
    public function __construct(AysoVolCert ...$items)
    {
        parent::__construct($items);
    }
    public function current() : AysoVolCert
    {
        return parent::current();
    }
    public function offsetGet($offset) : AysoVolCert
    {
        return parent::offsetGet($offset);
    }
    // Add a cert, only one cert per role, track oldest roleDate
    public function add(AysoVolCert $cert) : void
    {
        $certKey = $cert->role;
        if ($certKey === null) {
            return;
        }
        // New cert
        if (!$this->offsetExists($certKey)){
            $this->offsetSet($certKey,$cert);
            return;
        }
        $certOld = $this->offsetGet($certKey);

        // Higher badge
        if ($cert->sort > $certOld->sort) {
            if ($cert->roleDate > $certOld->roleDate) {
                $cert = $cert->withRoleDate($certOld->roleDate);
            }
            $this->offsetSet($certKey,$cert);
            return;

        }
        if ($certOld->roleDate > $cert->roleDate) {
            $certOld = $certOld->withRoleDate($cert->roleDate);
            $this->offsetSet($certKey,$certOld);
        }
    }
}