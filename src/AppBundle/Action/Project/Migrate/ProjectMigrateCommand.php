<?php
namespace AppBundle\Action\Project\Migrate;

use Symfony\Component\Console\Command\Command;
//  Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//  Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;
class ProjectMigrateCommand extends Command
{
    private $ng2014Conn;
    private $ng2016Conn;

    public function __construct(Connection $ng2014Conn, Connection $ng2016Conn)
    {
        parent::__construct();
        $this->ng2014Conn = $ng2014Conn;
        $this->ng2016Conn = $ng2016Conn;
    }
    protected function configure()
    {
        $this
            ->setName('project_migrate')
            ->setDescription('Migrate Project Database');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Migrate Project...\n");
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
        while($row = $retrieveStmt->fetch()) {

            $roles = unserialize(($row['roles']));
            if (!in_array('ROLE_USER',$roles)) {
                $roles[] = 'ROLE_USER';
            }
            $username = $row['username'];
            if ($username === $row['email']) {
                $username = $this->generateUniqueUsername($username);
            }
            $insertStmt->execute([
                $row['name'],
                $row['email'],
                $username,
                $row['personKey'],
                $row['salt'],
                $row['password'],
                implode(',',$roles),
            ]);
        }
    }
    private $uniqueUsernameStmt;

    private function generateUniqueUsername($email)
    {
        $emailParts = explode('@',$email);
        $username = $emailParts[0];

        if (!$this->uniqueUsernameStmt) {
            $sql = 'SELECT id FROM users WHERE username = ?';
            $this->uniqueUsernameStmt = $this->ng2016Conn->prepare($sql);
        }
        $stmt = $this->uniqueUsernameStmt;

        $cnt = 1;
        $usernameTry = $username;
        while(true) {
            $stmt->execute([$usernameTry]);
            if (!$stmt->fetch()) {
                return($usernameTry);
            }
            $cnt++;
            $usernameTry = $username . $cnt;
        }
        return null;
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
INSERT INTO project_persons 
(projectKey,personKey,orgKey,fedKey,registered,name,email,phone,gender,age,refereeBadge,refereeUpgrading) 
VALUES(?,?,?,?,?,?,?,?,?,?,?,?)
EOD;
        $insertStmt = $this->ng2016Conn->prepare($sql);

        while ($row = $retrieveStmt->fetch()) { //var_dump($row); die();

            $name = $this->generateUniqueProjectName($row['projectKey'],$row['name']);

            $age = null;
            if ($row['dob']) {
                $d1 = \DateTime::createFromFormat('Y-m-d', $row['dob']);
                $d2 = \DateTime::createFromFormat('Y-m-d', '2014-07-02');
                $age = $d1->diff($d2)->y;
            }
            $registered = true;

            $insertStmt->execute([
                $row['projectKey'],
                $row['personKey'],
                $row['orgKey'],
                $row['fedKey'],
                $registered,
                $name,
                $row['email'],
                $row['phone'],
                $row['gender'],
                $age,
                $row['refereeBadge'],
                $row['refereeUpgrading'],
            ]);
        }
    }
    private $uniqueProjectNameStmt;

    private function generateUniqueProjectName($projectKey,$name)
    {
        if (!$this->uniqueProjectNameStmt) {
            $sql = 'SELECT id FROM project_persons WHERE projectKey = ? AND name = ?';
            $this->uniqueProjectNameStmt = $this->ng2016Conn->prepare($sql);
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
            //echo sprintf("%s\n",$nameTry);
        }
        return null;
    }

}