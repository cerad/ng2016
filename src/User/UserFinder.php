<?php declare(strict_types=1);

namespace Zayso\User;

use Zayso\Common\Contract\UserInterface;

final class UserFinder
{
    private $userConn;

    public function __construct(UserConnection $userConn)
    {
        $this->userConn = $userConn;
    }
    public function find(string $identifier) : ?UserInterface
    {
        $identifier = trim($identifier);

        if (!$identifier) return null;

        // Data Mapper?
        $sql = <<<EOT
SELECT 
    id        AS userId,
    name      AS name,
    username  AS username,
    personKey AS personId,
    email     AS email,
    salt      AS salt,
    password  AS password,
    enabled   AS enabled,
    locked    AS locked,
    roles     AS roles
FROM  users 
WHERE username = ? OR email = ? OR personKey = ? OR providerKey = ? OR passwordToken = ? OR emailToken = ?
EOT;

        $stmt = $this->userConn->executeQuery($sql,[$identifier,$identifier,$identifier,$identifier,$identifier,$identifier]);

        $row = $stmt->fetch();
        if (!$row) return null;

        $row['roles'] = explode(',',$row['roles']);
        $row['enabled'] = (bool)$row['enabled'];
        $row['locked']  = (bool)$row['enabled'];

        return new User(
            $row['userId'],$row['name'],$row['username'],$row['personId'],$row['email'],
            $row['salt'],$row['password'],$row['enabled'],$row['locked'],$row['roles']
        );
    }
}