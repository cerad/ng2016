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

    private $users;
    private $usersMap = [];

    public function __construct
    (
        Connection $userConn,
        $users = []
    )
    {
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
            'user.personKey AS personKey',
        ]);
        $qb->from('users', 'user');

        return $qb;
    }
    private function createQueryBuilderForUser2014()
    {
        $qb = $this->userConn->createQueryBuilder();

        $qb->addSelect([
            'user.id           AS id',
            'user.username     AS username',
            'user.email        AS email',
            'user.salt         AS salt',
            'user.password     AS password',
            'user.roles        AS roles',

            'user.person_guid  AS personKey',

            'user.account_name    AS name',
            'user.account_enabled AS enabled',
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
        $user['username'] = $row['username'];
        $user['email']    = $row['email'];
        $user['enabled']  = $row['enabled'];
        $user['salt']     = $row['salt'];
        $user['password'] = $row['password'];

        $user['roles'] = explode(',',$row['roles']);

        $user['name']      = $row['name'];
        $user['personKey'] = $row['personKey'];
        
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
        $userId = $user->id;
        
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
