<?php

namespace AppBundle\Action\GameOfficial;

use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\Schedule2016\ScheduleGameOfficial;

use AppBundle\Action\Project\User\ProjectUser as User;

use Doctrine\DBAL\Connection;

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

    public function __construct(
        Connection $gameConn,
        Connection $regPersonConn,
        AccessDecisionManagerInterface $decisionManager
    ) {
        $this->gameConn        = $gameConn;
        $this->regPersonConn   = $regPersonConn;
        $this->decisionManager = $decisionManager;
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

        throw new \LogicException('This code should not be reached!');
    }

    private function canView($subject, TokenInterface $token, User $user)
    {
        // if they can edit, they can view
        if ($this->canEdit($subject, $token, $user)) {
            return true;
        }
        return true;
    }

    private function canEdit($subject, TokenInterface $token, User $user)
    {
        // Assignor can do anything
        if ($this->decisionManager->decide($token, ['ROLE_ASSIGNOR'])) {
            //return true;
        }
        // Must be a referee
        if (!$this->decisionManager->decide($token, ['ROLE_REFEREE'])) {
            return false;
        }

        // Danger
        /** @var GameOfficial $gameOfficial */
        $gameOfficial = $subject;

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

        return false;
    }
}
