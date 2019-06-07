<?php

namespace AppBundle\Action\GameOfficial;

use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\RegPerson\RegPersonFinder;
<<<<<<< HEAD
use AppBundle\Action\Schedule2019\ScheduleGameOfficial;
=======
use AppBundle\Action\Schedule\ScheduleGameOfficial;
>>>>>>> ng2019x2

use AppBundle\Action\Project\User\ProjectUser as User;

use Doctrine\DBAL\Connection;

use LogicException;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;


class GameOfficialVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';

    private $gameConn;
    private $regPersonConn;
    private $decisionManager;
    private $regPersonFinder;

    public function __construct(
        Connection $gameConn,
        Connection $regPersonConn,
        AccessDecisionManagerInterface $decisionManager,
        RegPersonFinder $regPersonFinder
    ) {
        $this->gameConn        = $gameConn;
        $this->regPersonConn   = $regPersonConn;
        $this->decisionManager = $decisionManager;
        $this->regPersonFinder = $regPersonFinder;
    }
    protected function supports($attribute, $subject)
    {
         if (!in_array($attribute, array(self::VIEW, self::EDIT))) {
            return false;
        }
        if ($subject instanceof GameOfficial || $subject instanceof ScheduleGameOfficial) {
            return true;
        }
        return false;
    }
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }
        switch($attribute) {
            case self::VIEW:
                return $this->canView($subject, $token, $user);
            case self::EDIT:
                return $this->canEdit($subject, $token, $user);
        }

        throw new LogicException('This code should not be reached!');
    }
    // Determine if they can link or not
    private function canView($gameOfficial, TokenInterface $token, User $user)
    {
        // Assignor can do anything
        if ($this->decisionManager->decide($token, ['ROLE_ASSIGNOR'])) {
            return true;
        }
        // Must be a referee
        if (!$this->decisionManager->decide($token, ['ROLE_REFEREE'])) {
            return false;
        }

        // Only certain games can be signed up for
        if ($gameOfficial->assignRole !== 'ROLE_REFEREE') {
            return false;
        }
        // Maybe verify referee is approved?

        // Can see empty games
        if ($gameOfficial->regPersonId === null) {
            // TODO Check for conflicts
            return true;
        }
        // Can see my games
        if ($gameOfficial->phyPersonId === $user->getPersonId()) {
            return true;
        }
        // Can edit my crew's games

        // Cache the crew ids for the current user
        // TOD Use physical person id
        $userRegPersonId = $user->getRegPersonId();
        if (!$this->regPersonCrewIds) {
            $this->regPersonCrewIds = $this->regPersonFinder->findRegPersonPersonIds($userRegPersonId);
        }
        if (isset($this->regPersonCrewIds[$gameOfficial->regPersonId])) {
            return true;
        }
        return false;
    }
    private function canEdit($gameOfficial, TokenInterface $token, User $user)
    {
        // Assignor can do anything
        if ($this->decisionManager->decide($token, ['ROLE_ASSIGNOR'])) {
            return true;
        }
        // Must be able to view
        if (!$this->canView($gameOfficial, $token, $user)) {
            return false;
        }
        
        $regPersonId = $gameOfficial->regPersonId;
        if (!$regPersonId) {
            // Empty slot, use current user
            $regPersonId = $user->getRegPersonId();
        }
        
        // Must be approved to actually make a change
        $isApproved = $this->regPersonFinder->isApprovedForRole('ROLE_REFEREE',$regPersonId);
        if (!$isApproved) {
            return false;
        }
        // Go crazy and check for conflicts here?  Wish I had a context for returning messages

        return true;
    }

    private $regPersonCrewIds;
}
