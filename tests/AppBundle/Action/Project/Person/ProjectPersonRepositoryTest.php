<?php
namespace Tests\AppBundle\Action\Project\Person;

use AppBundle\Action\Project\ProjectFactory;

use AppBundle\Action\Project\Person\ProjectPersonRepository;

use Symfony\Component\Yaml\Yaml;

use Doctrine\DBAL\Connection;
use Tests\AppBundle\AbstractTestDatabase;

class ProjectPersonRepositoryTest extends AbstractTestDatabase
{
    /** @var  ProjectPersonRepository */
    protected $projectPersonRepository;

    public function setUp()
    {
        $this->databaseNameKey = 'database_name_users';

        parent::setUp();

        $this->projectPersonRepository = new ProjectPersonRepository($this->conn, new ProjectFactory());
    }
    public function testFindOfficials()
    {
        $officials = $this->projectPersonRepository->findOfficials('AYSONationalGames2014');

        // 1314 registered, 1049 would referee, 762 actually refereed!
        $this->assertCount(1049,$officials);
        
        $official = $officials[9];

        //var_dump($official);

        $this->assertEquals('Adrian Backer',$official['name']);
        $this->assertEquals('2F1297A9-920E-44F1-8A75-3B52DFADA8F2',$official['personKey']);
        $this->assertEquals('AYSOR0122',$official['orgKey']);
        $this->assertEquals('Advanced', $official['badge']);

    }
}