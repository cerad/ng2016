<?php declare(strict_types=1);

namespace Zayso\Common\Contract;

/**
 * @property-read int userId
 *
 * @property-read string name
 * @property-read string email
 * @property-read string username
 *
 * @property-read bool isEnabled
 * @property-read bool isLocked
 * @property-read bool isRegistered
 *
 * @property-read string projectId
 * @property-read string personId
 *
 * @property-read array roles
 */
interface UserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{

}