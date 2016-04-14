<?php
namespace AppBundle\Action\Project\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/* ==========================================================
 * Not sure how this will all play out but instead of using a repository
 * try querying directly from here and see what happens to maintainable
 */
class ProjectUserProvider implements UserProviderInterface
{
    /** @var  Connection */
    private $userConn;

    /** @var  Connection */
    private $projectPersonConn;

    private $users;
    private $usersMap = [];

    private $projectKey = '';

    public function __construct
    (
        $projectKey,
        Connection $userConn,
        Connection $projectPersonConn,
        $users = []
    )
    {
        $this->projectKey        = $projectKey;
        $this->projectPersonConn = $projectPersonConn;

        $this->users    = $users;
        $this->userConn = $userConn;

        foreach($users as $username => $user) {
            $this->usersMap[(int)$user['id']] = $username;
        }
    }
    /**
     * @return QueryBuilder
     */
    private function createQueryBuilderForUser()
    {
        $qb = $this->userConn->createQueryBuilder();

        $qb->addSelect([
            'user.id           AS id',
            'user.username     AS username',
            'user.email        AS email',
            'user.salt         AS salt',
            'user.password     AS password',
            'user.roles        AS roles',
            
            'user.name      AS name',
            'user.enabled   AS enabled',
            'user.locked    AS locked',
            'user.personKey AS personKey',
        ]);
        $qb->from('users', 'user');

        return $qb;
    }
    /**
     * @param $qb QueryBuilder
     * @return ProjectUser|null
     */
    private function loadUser(QueryBuilder $qb)
    {
        $stmt = $qb->execute();
        $row  = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $user = new ProjectUser();
        $user['id']       = $row['id'];
        $user['name']      = $row['name'];
        $user['email']    = $row['email'];
        $user['username'] = $row['username'];
        $user['enabled']  = $row['enabled'];
        $user['locked' ]  = $row['locked'];
        $user['salt']     = $row['salt'];
        $user['password'] = $row['password'];

        $roles = explode(',',$row['roles']);

        $personKey  = $row['personKey'];
        $projectKey = $this->projectKey;

        $user['personKey']  = $personKey;
        $user['projectKey'] = $projectKey;
        $user['registered'] = null; // default value but okay to foc

        $qb = $this->projectPersonConn->createQueryBuilder();
        $qb->select([
            'projectPerson.registered',
            'projectPersonRole.role',
            'projectPersonRole.active',
        ]);
        $qb->from('projectPersons','projectPerson');
        $qb->leftJoin(
            'projectPerson',
            'projectPersonRoles',
            'projectPersonRole',
            'projectPersonRole.projectPersonId = projectPerson.id'
        );
        $qb->where('projectPerson.projectKey = ? AND projectPerson.personKey = ?');
        $qb->setParameters([$projectKey,$personKey]);

        $stmt = $qb->execute();

        while($row  = $stmt->fetch()) {

            $user['registered'] = $row['registered'];

            if ($row['active']) {
                $role = $row['role'];
                if (!in_array($role, $roles)) {
                    $roles[] = $role;
                }
            }
        }
        $user['roles'] = $roles;

        return $user;
    }
    /**
     * @param $username string
     * @return ProjectUser|null
     */
    private function loadUserFromMemory($username)
    {
        $row = isset($this->users[$username]) ? $this->users[$username] : null;
        if (!$row) return null;

        $user = new ProjectUser();

        $user['username'] = $username;

        $user['id']    = $row['id'];
        $user['name']  = $row['name'];
        $user['email'] = $row['email'];

        $user['roles'] = [$row['role']];

        $user['registered'] = true;
        $user['projectKey'] = $this->projectKey;
        $user['personKey' ] = '???';

        return $user;
    }
    /**
     * @param $username string
     * @return ProjectUser
     */
    public function loadUserByUsername($username)
    {
        $qb = $this->createQueryBuilderForUser();

        $qb->where(('user.username = :username OR user.email = :email OR user.providerKey = :providerKey'));
        
        $qb->setParameters([
            'username'    => $username,
            'email'       => $username,
            'providerKey' => $username,
        ]);

        $user = $this->loadUser($qb);
        if ($user) {
            return $user;
        }
        $user = $this->loadUserFromMemory($username);
        if ($user) {
            return $user;
        }

        // Check for social network identifiers
        
        // See if a fed person exists
        /*
        $event = new FindPersonEvent($username);
        
        $this->dispatcher->dispatch(FindPersonEvent::FindByFedKeyEventName,$event);
        
        $person = $event->getPerson();
        if ($person)
        {
            $user = $this->userManager->findUserByPersonGuid($person->getGuid());
            if ($user) return $user;
        }
        */
        // Bail
        throw new UsernameNotFoundException('User Not Found: ' . $username);
    }
    public function refreshUser(UserInterface $user)
    {
        $userClass = ProjectUser::class;
        if (!($user instanceOf $userClass)) {
            throw new UnsupportedUserException();
        }
        /** @var ProjectUser $user */
        $userId = $user['id'];
        
        $qb = $this->createQueryBuilderForUser();

        $qb->andWhere(('user.id = :id'));

        $qb->setParameter('id',$userId);

        $user = $this->loadUser($qb);

        if ($user) {
            return $user;
        }
        $username = isset($this->usersMap[$userId]) ? $this->usersMap[$userId] : null;
        if ($username) {
            return $this->loadUserFromMemory($username);
        }
        throw new UsernameNotFoundException('User ID Not Found: ' . $userId);
    }
    public function supportsClass($class)
    {
        $userClass = ProjectUser::class;
        return ($class instanceOf $userClass) ? true: false;
    }
}
?>
