<?php declare(strict_types=1);

namespace Zayso\Common\Locator;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Zayso\Common\DataTransformer\PhoneTransformer;
use Zayso\Fed\Ayso\AysoIdTransformer;

// TODO Note: this really should be project aware perhaps
// Keep for now, quite a few transformers are project independent
class DataTransformerLocator implements ServiceSubscriberInterface
{
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }
    public function has(string $id) : bool
    {
        return $this->locator->has($id);
    }
    public function get(string $id) : ?DataTransformerInterface
    {
        return $this->locator->get($id);
    }
    public static function getSubscribedServices()
    {
        return [
            'phone_transformer'  => PhoneTransformer::class,
            'aysoid_transformer' => AysoIdTransformer::class,
        ];
    }
}