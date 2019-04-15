<?php declare(strict_types = 1);

namespace Zayso\Common\Traits;

use AppBundle\Action\Project\User\ProjectUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait AuthenticationTrait
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @required */
    public function setOnceTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $this->tokenStorage ?: $tokenStorage;
    }
    /* Directly copied from ControllerTrait */
    protected function getUser() : ?ProjectUser
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }
        /** @var ProjectUser $userx */
        $userx = $user; // Just to keep the IDE code checker happy
        return $userx;
    }
}
