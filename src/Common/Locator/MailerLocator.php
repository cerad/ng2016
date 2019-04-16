<?php declare(strict_types=1);

namespace Zayso\Common\Locator;

use Swift_Mailer;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;


/**
 * This is probably overkill but I wanted an example of using the service subscriber
 * Basically lazy loads the mailer object
 */
class MailerLocator implements ServiceSubscriberInterface
{
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }
    public function getMailer() : Swift_Mailer
    {
        return $this->locator->get('mailer');
    }
    public static function getSubscribedServices()
    {
        return [
            'mailer' => Swift_Mailer::class,
        ];
    }
}