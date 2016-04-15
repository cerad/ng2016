<?php
namespace AppBundle\Action\Project\User;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class ProjectUserRepository
{
    /** @var  Connection */
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
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

    public function find($projectKey,$personKey)
    {
        $qb = $this->createProjectPersonQueryBuilder();
        $qb->where('projectPerson.projectKey = ? AND projectPerson.personKey = ?');
        $qb->setParameters([$projectKey,$personKey]);
        
        $stmt = $qb->execute();
        $projectPerson = $stmt->fetch();
        if (!$projectPerson) return null;
        
        $projectPerson['roles'] = [];

        return $projectPerson;
    }
    /** 
     * @return QueryBuilder
     */
    private function createProjectPersonQueryBuilder()
    {
        $qb = $this->conn->createQueryBuilder();
        $qb->select([
            'projectPerson.id         AS id',
            'projectPerson.projectKey AS projectKey',
            'projectPerson.personKey  AS personKey',
            'projectPerson.orgKey     AS orgKey',
            'projectPerson.fedKey     AS fedKey',
            'projectPerson.name       AS name',
            'projectPerson.email      AS email',

            'projectPerson.registered AS verified',
            'projectPerson.registered AS registered',
        ]);
        $qb->from('projectPersons','projectPerson');
        return $qb;
    }
}
