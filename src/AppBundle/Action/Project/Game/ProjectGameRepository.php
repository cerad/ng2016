<?php
namespace AppBundle\Action\Project\Game;

use Doctrine\DBAL\Connection;

class ProjectGameRepository
{
    /** @var Connection  */
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    /** 
     * @param string $projectKey
     * @param integer $number
     * @return ProjectGame 
     */
    public function create($projectKey,$number)
    {
        $game = new ProjectGame($projectKey,$number);
        
        return $game;
    }
}