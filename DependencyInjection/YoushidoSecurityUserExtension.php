<?php

namespace Youshido\SecurityUserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class YoushidoSecurityUserExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $prefix = 'youshido_security_user';
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $configs = $this->prepareConfigs($config);
        foreach ($configs as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $prefix, $key), $value);
        }
        $container->setParameter('youshido_security_user.mailer.from', $config['mailer']['from']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    private function prepareConfigs($configs)
    {
        $result = [];

        foreach ($configs as $key => $innerConfig) {
            if (is_array($innerConfig)) {
                $innerConfigPrepared = $this->prepareConfigs($innerConfig);

                foreach ($innerConfigPrepared as $innerKey => $innerValue) {
                    $result[sprintf('%s.%s', $key, $innerKey)] = $innerValue;
                }
            } else {
                $result[$key] = $innerConfig;
            }
        }

        return $result;
    }
}
