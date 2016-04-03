<?php
namespace AppBundle\Action\Project\Person;

use Cerad\Bundle\ProjectBundle\ProjectFactory;

use Doctrine\DBAL\Connection;

class ProjectPersonRepository
{
    /** @var  Connection */
    private $conn;

    /** @var ProjectFactory */
    private $projectFactory;

    public function __construct(Connection $conn, ProjectFactory $projectFactory)
    {
        $this->conn = $conn;
        $this->projectFactory = $projectFactory;
    }

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
