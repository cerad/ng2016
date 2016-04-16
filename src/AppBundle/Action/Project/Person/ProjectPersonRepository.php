<?php
namespace AppBundle\Action\Project\Person;

use AppBundle\Action\Project\ProjectFactory;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class ProjectPersonRepository
{
    /** @var  Connection */
    private $conn;

    /** @var ProjectFactory */
    private $projectFactory;

    public function __construct(Connection $conn, ProjectFactory $projectFactory = null)
    {
        $this->conn = $conn;
        $this->projectFactory = $projectFactory;
    }
    public function save($projectPerson,$projectPersonOriginal = null)
    {
        $id = $projectPerson['id'] ? : null;
        if (!$id) {
            return $this->insert($projectPerson);
        }
        if (isset($projectPersonOriginal['name']) && ($projectPersonOriginal['name'] != $projectPerson['name'])) {
            $projectPerson['name'] = $this->generateUniqueName($projectPerson['projectKey'],$projectPerson['name']);
        }

        $sql = <<<EOD
UPDATE projectPersons SET
orgKey = ?,     fedKey = ?,
registered = ?, verified = ?,
name   = ?,     email = ?, phone = ?,
gender = ?,     age = ?, 
notes = ?,      notesUser = ?,
plans = ?,      avail = ?
WHERE projectKey = ? AND personKey = ?
EOD;
        $stmt = $this->conn->prepare(($sql));
        $stmt->execute([
            $projectPerson['orgKey'],
            $projectPerson['fedKey'],
            $projectPerson['registered'],
            $projectPerson['verified'],
            $projectPerson['name'  ],
            $projectPerson['email' ],
            $projectPerson['phone' ],
            $projectPerson['gender'],
            $projectPerson['age'],
            $projectPerson['notes'],
            $projectPerson['notesUser'],
            isset($projectPerson['plans']) ? serialize($projectPerson['plans']) : null,
            isset($projectPerson['avail']) ? serialize($projectPerson['avail']) : null,
            $projectPerson['projectKey'],
            $projectPerson['personKey'],

        ]);

        return $projectPerson;
    }
    private function insert($projectPerson)
    {
        // Prevent dups
        $projectPerson['name'] = $this->generateUniqueName($projectPerson['projectKey'],$projectPerson['name']);
        
        $sql = <<<EOD
INSERT INTO projectPersons
(projectKey,personKey,orgKey, fedKey,registered,verified, name,email,phone, gender,age,notes, notesUser,plans,avail)
VALUES
(?,?,?, ?,?,?, ?,?,?, ?,?,?, ?)
EOD;
        $stmt = $this->conn->prepare(($sql));
        $stmt->execute([
            $projectPerson['projectKey'],
            $projectPerson['personKey'],
            $projectPerson['orgKey'],
            $projectPerson['fedKey'],
            $projectPerson['registered'],
            $projectPerson['verified'],
            $projectPerson['name'],
            $projectPerson['email'],
            $projectPerson['phone'],
            $projectPerson['gender'],
            $projectPerson['age'],
            $projectPerson['notes'],
            $projectPerson['notesUser'],
            isset($projectPerson['plans']) ? serialize($projectPerson['plans']) : null,
            isset($projectPerson['avail']) ? serialize($projectPerson['avail']) : null,
        ]);
        $projectPerson['id'] = $this->conn->lastInsertId();

        return $projectPerson;
    }
    public function find($projectKey,$personKey)
    {
        $qb = $this->createProjectPersonQueryBuilder();
        $qb->where('projectPerson.projectKey = ? AND projectPerson.personKey = ?');
        $qb->setParameters([$projectKey,$personKey]);
        
        $stmt = $qb->execute();
        $projectPerson = $stmt->fetch();
        if (!$projectPerson) return null;

        $projectPerson['plans'] = isset($projectPerson['plans']) ? unserialize($projectPerson['plans']) : null;
        $projectPerson['avail'] = isset($projectPerson['avail']) ? unserialize($projectPerson['avail']) : null;

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
            'projectPerson.phone      AS phone',
            'projectPerson.gender     AS gender',
            'projectPerson.age        AS age',
            'projectPerson.plans      AS plans',
            'projectPerson.avail      AS avail',
            'projectPerson.notes      AS notes',
            'projectPerson.notesUser  AS notesUser',

            'projectPerson.verified   AS verified',
            'projectPerson.registered AS registered',
        ]);
        $qb->from('projectPersons','projectPerson');
        return $qb;
    }
    public function create($projectKey,$personKey,$name,$email)
    {
        $name = $this->generateUniqueName($projectKey,$name);
        return [

            'id' => null,

            'projectKey' => $projectKey,
            'personKey'  => $personKey,
            'name'       => $name,
            'email'      => $email,
            
            'orgKey' => null,
            'fedKey' => null,
            'phone'  => null,
            'gender' => null,
            'age'    => null,
            
            'verified'   => null,
            'registered' => null,

            'notes'     => null,
            'notesUser' => null,

            'plans' => null,
            'avail' => null,
        ];
    }
    /** @var  Statement */
    private $uniqueProjectNameStmt;

    public function generateUniqueName($projectKey,$name)
    {
        if (!$this->uniqueProjectNameStmt) {
            $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND name = ?';
            $this->uniqueProjectNameStmt = $this->conn->prepare($sql);
        }
        $stmt = $this->uniqueProjectNameStmt;

        $cnt = 1;
        $nameTry = $name;
        while(true) {
            $stmt->execute([$projectKey,$nameTry]);
            if (!$stmt->fetch()) {
                return($nameTry);
            }
            $cnt++;
            $nameTry = sprintf('%s(%d)',$name,$cnt);
        }
        return null;
    }
    // ng2014 format
    public function findOfficials($projectKey)
    {
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'projectPerson.id         AS id',
            'projectPerson.project_id AS projectKey',
            'projectPerson.notes      AS notes',
            'projectPerson.status     AS status',
            'projectPerson.basic      AS plans',
            'projectPerson.avail      AS avail',

            'projectPerson.person_name AS name',

            'physicalPerson.guid   AS personKey',

            'physicalPerson.email  AS email',
            'physicalPerson.phone  AS phone',
            'physicalPerson.gender AS gender',
            'physicalPerson.dob    AS dob',

            // Need these?
            //'physicalPerson.name_first AS nameFirst',
            //'physicalPerson.name_last  AS nameLast',
            //'physicalPerson.name_nick  AS nameNick',
            
            'fed.org_key   AS orgKey', // AYSOR0894, ever convert to sar?
            'fedCert.badge AS badge',
        ]);
        $qb->from('person_plans', 'projectPerson');

        $qb->leftJoin('projectPerson','persons','physicalPerson','physicalPerson.id = projectPerson.id');

        $qb->leftJoin('physicalPerson','person_feds','fed','fed.person_id = physicalPerson.id AND fed.fed_role = :fedRole');
        $qb->setParameter('fedRole','AYSOV');

        $qb->leftJoin('fed','person_fed_certs','fedCert','fedCert.person_fed_id = fed.id AND fedCert.role = :fedCertRole');
        $qb->setParameter('fedCertRole','Referee');

        $qb->andWhere('projectPerson.project_id = :projectKey');
        $qb->setParameter('projectKey', $projectKey);

        // Should query on verified but did not use for 2014

        $qb->addOrderBy('name','ASC');

        $stmt = $qb->execute();
        $projectPersons = [];
        while ($projectPerson = $stmt->fetch()) {

            // Using @ because some control codes have snuck in see id == 108
            //  in �sorry
            // $filtered = filter_var ($projectPerson['plans'], FILTER_SANITIZE_STRING);
            // $projectPerson['plans'] = $plans = unserialize($filtered);

            $projectPerson['plans'] = $plans = @unserialize($projectPerson['plans']);

            if ($plans['attending'] !== 'no' && $plans['refereeing'] !== 'no') {
                $projectPersons[] = $projectPerson;
            }
        }
        return $projectPersons;
    }
}
