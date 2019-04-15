<?php

use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Contract\ViewInterface;

use Zayso\Common\Locator\DataTransformerLocator;
use Zayso\Common\Locator\ViewLocator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Form\DataTransformerInterface;

class AppKernel extends Kernel implements CompilerPassInterface
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            //new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            //new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            //new Cerad\Bundle\UserBundle\CeradUserBundle(),
            //new \Cerad\Bundle\ProjectBundle\CeradProjectBundle(),
//            new \Cerad\Bundle\AysoBundle\CeradAysoBundle(),
            new AppBundle\AppBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\TwigBundle\TwigBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
    protected function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(ActionInterface::class)
            ->addTag('controller.service_arguments');
        $container->registerForAutoconfiguration(ViewInterface::class)
            ->addTag('zayso_view');
        //$container->registerForAutoconfiguration(ProjectInterface::class)
        //    ->addTag('project.base');
        //$container->registerForAutoconfiguration(ProjectTemplateInterface::class)
        //    ->addTag('project.template');
        $container->registerForAutoconfiguration(DataTransformerInterface::class)
            ->addTag('zayso_data_transformer');
    }
    public function process(ContainerBuilder $container)
    {
        $this->addLocator($container, DataTransformerLocator::class, ['zayso_data_transformer']);
        $this->addLocator($container, ViewLocator::class, ['zayso_view']);

    }
    private function addLocator(ContainerBuilder $container, string $locatorId, array $tags ) : void
    {
        $ids = [];
        foreach($tags as $tag) {
            foreach ($container->findTaggedServiceIds($tag) as $id => $tagsUsed) {
                $ids[$id] = new Reference($id);
            }
        }
        $locator = $container->getDefinition($locatorId);
        $locator->setArguments([$ids]);
    }
}
