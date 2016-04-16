<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AppExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        //$configuration = new Configuration();
        //$config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        
        $loader->load('services/game.yml');
        $loader->load('services/results.yml');
        $loader->load('services/schedule.yml');

        $actionLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Action'));

        $actionLoader->load('App/services.yml');
        $actionLoader->load('Project/services.yml');
        $actionLoader->load('Project/User/services.yml');
        $actionLoader->load('Project/Person/services.yml');

        $actionLoader->load('Physical/Ayso/services.yml');
        $actionLoader->load('Physical/Person/services.yml');
    }
}
