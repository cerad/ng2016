<?php
namespace AppBundle\Action\Project\Migrate;

use AppBundle\Action\Project\User\ProjectUserRepository;
use AppBundle\Action\Project\Person\ProjectPersonRepository;

use Symfony\Component\Console\Command\Command;
//  Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//  Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
class ProjectMigrateCommand extends Command
{
    private $maxCnt = 100;

    private $ng2014Conn;
    private $ng2016Conn;
    private $users = [];

    private $projectUserRepository;
    private $projectPersonRepository;

    public function __construct(Connection $ng2014Conn, Connection $ng2016Conn)
    {
        parent::__construct();
        $this->ng2014Conn = $ng2014Conn;
        $this->ng2016Conn = $ng2016Conn;

        $this->projectUserRepository   = new ProjectUserRepository  ($ng2016Conn);
        $this->projectPersonRepository = new ProjectPersonRepository($ng2016Conn);
    }
    protected function configure()
    {
        $this
            ->setName('project:migrate')
            ->setDescription('Migrate Project Database');
    }
    private function clearDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    private function createDatabase(Connection $conn)
    {
        $cmd = sprintf("mysql -u%s -p%s %s < schema2016.sql",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase()
        );
        exec($cmd);
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Migrate Project...\n");

        $this->clearDatabase ($this->ng2016Conn);
        $this->createDatabase($this->ng2016Conn);

        $this->migrateUsers();
        $this->migrateProjectPersons();

        echo sprintf("Migrate Project Completed.\n");
    }
    private function migrateUsers()
    {
        $sql = 'INSERT INTO users (name,email,username,personKey,salt,password,roles) VALUES(?,?,?,?,?,?,?)';
        $insertStmt = $this->ng2016Conn->prepare($sql);

        $qb = $this->ng2014Conn->createQueryBuilder();

        $qb->select([
            'user.username     AS username',
            'user.email        AS email',
            'user.salt         AS salt',
            'user.password     AS password',
            'user.roles        AS roles',

            'user.person_guid  AS personKey',

            'user.account_name    AS name',
        ]);
        $qb->from('users', 'user');
        $retrieveStmt = $qb->execute();
        $cnt = 0;
        while(($row = $retrieveStmt->fetch()) && ($cnt++ < $this->maxCnt)) {

            $roles = unserialize($row['roles']);

            if (!in_array('ROLE_USER',$roles)) {
                $roles[] = 'ROLE_USER';
            }
            $roles = implode(',',$roles);

            $username = $row['username'];
            if ($username === $row['email']) {
                $username = $this->projectUserRepository->generateUniqueUsernameFromEmail($row['email']);
            }
            $insertStmt->execute([
                $row['name'],
                $row['email'],
                $username,
                $row['personKey'],
                $row['salt'],
                $row['password'],
                $roles,
            ]);
            $row['username'] = $username;
            $row['roles']    = $roles;
            $this->users[$row['personKey']] = $row;
        }
    }
    private function migrateProjectPersons()
    {
        $qb = $this->ng2014Conn->createQueryBuilder();

        $qb->select([
            'projectPerson.project_id  AS projectKey',
            'physicalPerson.guid       AS personKey',
            'physicalPerson.email      AS email',
            'physicalPerson.phone      AS phone',
            'physicalPerson.gender     AS gender',
            'physicalPerson.dob        AS dob',
            'projectPerson.person_name AS name',
            'fed.fed_key     AS fedKey',
            'fed.org_key     AS orgKey',
            'fed.mem_year    AS regYear',
            'cert.badge      AS refereeBadge',
            'cert.badge_user AS refereeBadgeUser',
            'cert.upgrading  AS refereeUpgrading',
        ]);
        $qb->from('person_plans','projectPerson');

        $qb->leftJoin('projectPerson','persons','physicalPerson','physicalPerson.id = projectPerson.person_id');

        $qb->leftJoin('physicalPerson','person_feds','fed',
            'fed.person_id = projectPerson.id AND fed.fed_role = \'AYSOV\'');

        $qb->leftJoin('fed','person_fed_certs','cert',
            'cert.person_fed_id = fed.id AND cert.role = \'Referee\'');

        $retrieveStmt = $qb->execute();

        $sql = <<<EOD
INSERT INTO projectPersons 
(projectKey,personKey,orgKey,fedKey,regYear,registered,verified,name,email,phone,gender,age) 
VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
EOD;
        $insertProjectPersonStmt = $this->ng2016Conn->prepare($sql);

        $sql = <<<EOD
INSERT INTO projectPersonRoles
(projectPersonId,role,badge)
VALUES(?,?,?)
EOD;
        $insertProjectPersonRoleStmt = $this->ng2016Conn->prepare($sql);

        $cnt = 0;
        while (($row = $retrieveStmt->fetch()) && ($cnt++ < $this->maxCnt)) { //var_dump($row); die();

            $name = $this->projectPersonRepository->generateUniqueName($row['projectKey'],$row['name']);

            $age = null;
            if ($row['dob']) {
                $d1 = \DateTime::createFromFormat('Y-m-d', $row['dob']);
                $d2 = \DateTime::createFromFormat('Y-m-d', '2014-07-02');
                $age = $d1->diff($d2)->y;
            }
            $registered = true;
            $verified   = false;

            $fedKey = $row['fedKey'];
            if ($fedKey) {
                $fedKey = 'AYSOV:' . substr($fedKey,5);
            }
            $orgKey = $row['orgKey'];
            if ($orgKey) {
                $orgKey = 'AYSOR:' . substr($orgKey,5);
            }
            $insertProjectPersonStmt->execute([
                $row['projectKey'],
                $row['personKey'],
                $orgKey,
                $fedKey,
                $row['regYear'],
                $registered,
                $verified,
                $name,
                $row['email'],
                $row['phone'],
                $row['gender'],
                $age,
            ]);
            $projectPersonId = $this->ng2016Conn->lastInsertId();

            $insertProjectPersonRoleStmt->execute([
                $projectPersonId,
                'ROLE_REFEREE',
                $row['refereeBadge'],
            ]);
        }
    }
}