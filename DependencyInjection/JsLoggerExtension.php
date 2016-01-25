<?php

namespace Dawen\Bundle\JsLoggerBundle\DependencyInjection;

use Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface;
use Dawen\Bundle\JsLoggerBundle\Twig\JsLoggerTwigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JsLoggerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $twigDef = $container->getDefinition(JsLoggerTwigExtension::DIC_NAME);
        $twigDef->addArgument($config['enabled']);
        $twigDef->addArgument($config['allowed_levels']);

        if(true === $config['enabled']) {
            if(is_array($config['allowed_levels']) && !empty($config['allowed_levels'])) {
                $loggerDef = $container->getDefinition(JsLoggerInterface::DIC_NAME);
                $loggerDef->addArgument($config['allowed_levels']);
            }
        } else { // removes definition, if disabled
            $container->removeDefinition(JsLoggerInterface::DIC_NAME);
        }
    }
}
