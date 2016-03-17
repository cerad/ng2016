<?php

namespace Cerad\Bundle\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function createUser($params = null) { return new $this->_entityName($params); }

    public function flush()          { $this->_em->flush(); }
    public function persist($entity) { $this->_em->persist($entity); }
    public function remove ($entity) { $this->_em->remove ($entity); }

    /* =========================================================================
     * Still a bit uneasy about user repo vs user manager
     */
    public function findOneByPersonGuid($guid)
    {
        if (!$guid) return null;
        
        return $this->findOneBy(array('personGuid' => $guid));
    }
}
?>
