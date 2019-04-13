<?php
declare(strict_types=1);

namespace Zayso\Fed;

use \ArrayIterator;

class FedPersonCerts extends ArrayIterator
{
    public function __construct(FedPersonCert ...$items)
    {
        parent::__construct($items);
    }
    public function current() : FedPersonCert
    {
        return parent::current();
    }
    public function offsetGet($offset) : FedPersonCert
    {
        return parent::offsetGet($offset);
    }
    public function get($key) : ?FedPersonCert
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }
    // Add a cert, only one cert per role, track oldest roleDate
    public function add(FedPersonCert $cert) : void
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
                $cert->set('roleDate',$certOld->roleDate);
            }
            $this->offsetSet($certKey,$cert);
            return;

        }
        if ($certOld->roleDate > $cert->roleDate) {
            $certOld->set('roleDate',$cert->roleDate);
            $this->offsetSet($certKey,$certOld);
        }
    }
}