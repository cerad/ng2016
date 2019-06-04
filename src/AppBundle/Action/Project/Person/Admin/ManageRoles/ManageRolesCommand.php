<?php
namespace AppBundle\Action\Project\Person\Admin\ManageRoles;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ManageRolesCommand extends Command
{
    private $conn;

    private $projectKey;

    public function __construct(
        Connection  $conn,
        string $projectKey
    ) {
        parent::__construct();

        $this->conn = $conn;
        $this->projectKey = $projectKey;
    }
    protected function configure()
    {
        $this
            ->setName('manage:roles')
            ->setDescription('Managed Registered Person Roles')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Identifier')
            ->addArgument('role',       InputArgument::OPTIONAL, 'role');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $role       = $input->getArgument('role');
        $identifier = $input->getArgument('identifier');

        $sql = <<<EOD
SELECT id,projectKey,personKey,name,email 
FROM  projectPersons 
WHERE projectKey = ? AND (email = ? OR name LIKE ? OR id = ?)
EOD;
        $stmt = $this->conn->executeQuery($sql,[$this->projectKey, $identifier,'%' . $identifier . '%',$identifier]);
        $rows = $stmt->fetchAll();
        if (count($rows) === 0) {
            echo sprintf("Identifier NOT FOUND: %s\n",$identifier);
            return;
        }
        if (count($rows) > 1) {
            echo sprintf("Multiple records found for: %s\n",$identifier);
            foreach($rows as $row) {
                echo sprintf("%d %s %s\n",$row['id'],$row['email'],$row['name']);
            }
            return;
        }
        $regPerson = $rows[0];
        echo sprintf("Found: %d %s %s\n",$regPerson['id'],$regPerson['email'],$regPerson['name']);

        $sql = <<<EOD
SELECT id,role,badge FROM projectPersonRoles WHERE projectPersonId = ? AND active = true ORDER BY role;
EOD;
        $stmt = $this->conn->executeQuery($sql,[$regPerson['id']]);
        $roles = $stmt->fetchAll();
        $found = false;
        foreach($roles as $roleRow) {
            echo sprintf("ROLE %d %s %s\n",$roleRow['id'],$roleRow['role'],$roleRow['badge']);
            if ($role === $roleRow['role']) {
                $found = true;
                $this->conn->delete('projectPersonRoles',['id' => $roleRow['id']]);
                echo sprintf("Removed role %s\n",$role);
            }
        }
        if ($role && !$found) {
            // Add it
            $item = [
                'projectPersonId' => $regPerson['id'],
                'role' => $role,
            ];
            $this->conn->insert('projectPersonRoles',$item);
            echo sprintf("ADDED %s\n",$role);
        }
    }
}