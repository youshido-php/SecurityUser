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
        $rootNode = $treeBuilder->root('youshido_security_user');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('login_form')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/login.html.twig')->end()
                        ->scalarNode('register_form')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/register.html.twig')->end()
                        ->scalarNode('activation_success')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/register.html.twig')->end()

                        ->scalarNode('recovery_success')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/recovery_success.html.twig')->end()
                        ->scalarNode('recovery_form')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/recovery.html.twig')->end()

                        ->scalarNode('change_password_success')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/change_password_success.html.twig')->end()
                        ->scalarNode('change_password_error')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/recovery_error.html.twig')->end()
                        ->scalarNode('change_password_form')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Security/change_password.html.twig')->end()

                        ->scalarNode('register_letter')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Letters/register_letter.html.twig')->end()
                        ->scalarNode('recovery_letter')->cannotBeEmpty()->defaultValue('@YoushidoSecurityUser/Letters/recovery_letter.html.twig')->end()
                    ->end()
                ->end()
                ->arrayNode('redirects')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('register_success')->cannotBeEmpty()->defaultValue('homepage')->end()
                        ->scalarNode('on_failure')->cannotBeEmpty()->defaultValue('homepage')->end()
                        ->scalarNode('on_success')->cannotBeEmpty()->defaultValue('homepage')->end()
                        ->booleanNode('user_referer')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('send_mails')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('register')->defaultValue(true)->end()
                        ->scalarNode('recovery')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('registration')->cannotBeEmpty()->defaultValue('Youshido\SecurityUserBundle\Form\Type\SecuredUserType')->end()
                    ->end()
                ->end()
                ->arrayNode('mailer')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('from')->cannotBeEmpty()->defaultValue('from@test.com')->end()
                        ->arrayNode('subjects')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->children()
                                ->scalarNode('recovery')->cannotBeEmpty()->defaultValue('Recovery letter')->end()
                                ->scalarNode('register')->cannotBeEmpty()->defaultValue('Register letter')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('model')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
