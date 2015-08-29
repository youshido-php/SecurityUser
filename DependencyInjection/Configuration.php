<?php

namespace Youshido\SecurityUserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('youshido_user');

        $rootNode
            ->children()
                ->arrayNode('templates')
                    ->children()
                        ->scalarNode('login_form')->defaultValue('@YoushidoSecurityUser/Security/login.html.twig')->end()
                        ->scalarNode('register_form')->defaultValue('@YoushidoSecurityUser/Security/register.html.twig')->end()

                        ->scalarNode('recovery_success')->defaultValue('@YoushidoSecurityUser/Security/recovery_success.html.twig')->end()
                        ->scalarNode('recovery_form')->defaultValue('@YoushidoSecurityUser/Security/recovery.html.twig')->end()

                        ->scalarNode('change_password_success')->defaultValue('@YoushidoSecurityUser/Security/change_password_success.html.twig')->end()
                        ->scalarNode('change_password_error')->defaultValue('@YoushidoSecurityUser/Security/recovery_error.html.twig')->end()
                        ->scalarNode('change_password_form')->defaultValue('@YoushidoSecurityUser/Security/change_password.html.twig')->end()
                    ->end()
                ->end()
                ->arrayNode('redirects')
                    ->children()
                        ->scalarNode('register_success')->defaultValue('homepage')->end()
                    ->end()
                ->end()
                ->arrayNode('form')
                    ->children()
                        ->scalarNode('registration')->defaultValue('Youshido\SecurityUserBundle\Form\Type\UserType')->end()
                    ->end()
                ->end()
                ->arrayNode('model')
                    ->children()
                        ->scalarNode('registration')->defaultValue('Youshido\SecurityUserBundle\Entity\User')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
