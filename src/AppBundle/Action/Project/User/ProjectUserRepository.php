<?php
namespace AppBundle\Action\Project\User;

use AppBundle\Common\TokenGeneratorTrait;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
//  Doctrine\DBAL\Query\QueryBuilder;

class ProjectUserRepository
{
    use TokenGeneratorTrait;
    
    /** @var  Connection */
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    public function find($identifier)
    {
        $sql = 'SELECT * FROM users WHERE username = ? OR email = ? OR providerKey = ? OR passwordToken = ? OR emailToken = ?';
        $stmt = $this->conn->executeQuery($sql,[$identifier,$identifier,$identifier,$identifier,$identifier]);
        $row = $stmt->fetch();
        if (!$row) return null;
        
        $row['roles'] = explode(',',$row['roles']);
        
        return $row ? : null;
    }
    public function save($projectUser)
    {
        $row = $this->create(null,null,null,null);

        foreach(array_keys($row) as $key)
        {
            $row[$key] = isset($projectUser[$key]) ? $projectUser[$key] : $row[$key];
        }
        $row['roles'] = implode(',',$projectUser['roles']);
        
        $id = $row['id'];
        unset($row['id']);
        if ($id) {
            $this->conn->update('users',$row,['id' => $id]);
            return $projectUser;
        }
        $this->conn->insert('users',$row);
        $row['id'] = $this->conn->lastInsertId();
        return $row;
    }
    public function create($personKey,$name,$username,$email)
    {
        return [
            'id' => null,

            'name'      => $name,
            'username'  => $username,
            'personKey' => $personKey,
            
            'email'         => $email,
            'emailToken'    => null,
            'emailVerified' => false,

            'salt'          => null,
            'password'      => null,
            'passwordToken' => null,

            'enabled' => true,
            'locked'  => false,

            'roles' => ['ROLE_USER'],

            'providerKey' => null,
        ];
    }
    /* ==========================================================
     * Bunch of email username validation stuff
     */
    public function isEmailUnique($email)
    {
        $sql = 'SELECT id FROM users WHERE email = ? OR username = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email, $email]);
        return $stmt->fetch() ? false : true;
    }
    public function isUsernameUnique($username)
    {
        $sql = 'SELECT id FROM users WHERE username = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch() ? false : true;
    }
    /** @var  Statement */
    private $uniqueUsernameStmt;

    public function generateUniqueUsername($username)
    {
        if (!$this->uniqueUsernameStmt) {
            $sql = 'SELECT id FROM users WHERE username = ?';
            $this->uniqueUsernameStmt = $this->conn->prepare($sql);
        }
        $stmt = $this->uniqueUsernameStmt;

        $cnt = 2;
        $usernameTry = $username;
        while(true) {
            $stmt->execute([$usernameTry]);
            if (!$stmt->fetch()) {
                return($usernameTry);
            }
            $usernameTry = sprintf('%s%d',$username,$cnt++);
        }
        return null;
    }
    public function generateUniqueUsernameFromEmail($email)
    {
        // Derive username from email?
        $emailParts = explode('@',$email);
        $username   = $emailParts[0];
        return $this->generateUniqueUsername($username);
    }
}
