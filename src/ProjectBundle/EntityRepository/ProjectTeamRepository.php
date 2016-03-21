<?php
namespace Cerad\Bundle\ProjectBundle\EntityRepository;

use Doctrine\DBAL\Connection;

use Cerad\Bundle\ProjectBundle\Entity\ProjectTeam;

class ProjectTeamRepository
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    public function findAll()
    {
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_team.id         AS project_team__id',
            'project_team.keyx       AS project_team__key',
            'project_team.name       AS project_team__name',
            'project_team.projectKey AS project_team__project_key',
            'project_team.levelKey   AS project_team__level_key',
        ]);
        $qb->from('teams','project_team');

        $qb->andWhere('project_team.levelKey LIKE :level');
        $qb->andWhere("project_team.status = 'Active'");

        $qb->setParameter(':level','%_Core');

        $qb->addOrderBy('project_team.levelKey','DESC');

        $stmt = $qb->execute();
        $projectTeams = [];
        while($row = $stmt->fetch()) {
            $projectTeams[] = new ProjectTeam(
                $row['project_team__key'],
                $row['project_team__name'],
                $row['project_team__level_key']
            );
        }
        return $projectTeams;
    }
    // Move to game repo
    public function findProjectGames($projectTeamKeys = [])
    {
        if (count($projectTeamKeys) < 1) return [];
    }
}