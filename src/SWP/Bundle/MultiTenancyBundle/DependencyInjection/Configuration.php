<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancy Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\MultiTenancyBundle\DependencyInjection;

use SWP\Bundle\MultiTenancyBundle\Doctrine\ORM\OrganizationRepository;
use SWP\Bundle\MultiTenancyBundle\Doctrine\ORM\TenantDomainRepository;
use SWP\Bundle\MultiTenancyBundle\Doctrine\ORM\TenantRepository;
use SWP\Bundle\MultiTenancyBundle\Doctrine\PHPCR\OrganizationRepository as PHPCROrganizationRepository;
use SWP\Bundle\MultiTenancyBundle\Doctrine\PHPCR\TenantRepository as PHPCRTenantRepository;
use SWP\Bundle\MultiTenancyBundle\Factory\OrganizationFactory;
use SWP\Bundle\MultiTenancyBundle\Routing\TenantAwareRouter;
use SWP\Component\MultiTenancy\Factory\TenantFactory;
use SWP\Component\MultiTenancy\Model\Organization;
use SWP\Component\MultiTenancy\Model\OrganizationInterface;
use SWP\Component\MultiTenancy\Model\Tenant;
use SWP\Component\MultiTenancy\Model\TenantDomain;
use SWP\Component\MultiTenancy\Model\TenantDomainInterface;
use SWP\Component\MultiTenancy\Model\TenantInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
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
        $treeBuilder = new TreeBuilder('swp_multi_tenancy');
        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('use_orm_listeners')
                    ->defaultFalse()
                    ->info('Listeners which make sure that each entity is tenant aware (they append tenant code).')
                ->end()
                ->arrayNode('persistence')
                    ->validate()
                    ->ifTrue(function ($v) {
                        return count(array_filter($v, function ($persistence) {
                            return $persistence['enabled'];
                        })) > 1;
                    })
                    ->thenInvalid('Only one persistence layer can be enabled at the same time.')
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('phpcr')
                            ->addDefaultsIfNotSet()
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('basepath')->defaultValue('/swp')->end()
                                ->arrayNode('route_basepaths')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(['routes'])
                                    ->info('Route base paths names')
                                ->end()
                                ->scalarNode('content_basepath')
                                    ->defaultValue('content')
                                    ->info('Content base path name')
                                ->end()
                                ->scalarNode('menu_basepath')
                                    ->defaultValue('menu')
                                    ->info('Menu base path name')
                                ->end()
                                ->scalarNode('media_basepath')
                                    ->defaultValue('media')
                                    ->info('Media base path name')
                                ->end()
                                ->scalarNode('tenant_aware_router_class')
                                    ->defaultValue(TenantAwareRouter::class)
                                    ->info('Tenant aware router FQCN')
                                ->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('tenant')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->cannotBeEmpty()->defaultValue(Tenant::class)->end()
                                                ->scalarNode('interface')->cannotBeEmpty()->defaultValue(TenantInterface::class)->end()
                                                ->scalarNode('repository')->defaultValue(PHPCRTenantRepository::class)->end()
                                                ->scalarNode('factory')->defaultValue(TenantFactory::class)->end()
                                                ->scalarNode('object_manager_name')->defaultValue(null)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('organization')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->cannotBeEmpty()->defaultValue(Organization::class)->end()
                                                ->scalarNode('interface')->cannotBeEmpty()->defaultValue(OrganizationInterface::class)->end()
                                                ->scalarNode('repository')->defaultValue(PHPCROrganizationRepository::class)->end()
                                                ->scalarNode('factory')->defaultValue(OrganizationFactory::class)->end()
                                                ->scalarNode('object_manager_name')->defaultValue(null)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() // phpcr
                        ->arrayNode('orm')
                            ->addDefaultsIfNotSet()
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('tenant')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->cannotBeEmpty()->defaultValue(Tenant::class)->end()
                                                ->scalarNode('interface')->cannotBeEmpty()->defaultValue(TenantInterface::class)->end()
                                                ->scalarNode('repository')->defaultValue(TenantRepository::class)->end()
                                                ->scalarNode('factory')->defaultValue(TenantFactory::class)->end()
                                                ->scalarNode('object_manager_name')->defaultValue(null)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('tenantDomain')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->cannotBeEmpty()->defaultValue(TenantDomain::class)->end()
                                                ->scalarNode('interface')->cannotBeEmpty()->defaultValue(TenantDomainInterface::class)->end()
                                                ->scalarNode('repository')->defaultValue(TenantDomainRepository::class)->end()
                                                ->scalarNode('object_manager_name')->defaultValue(null)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('organization')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('model')->cannotBeEmpty()->defaultValue(Organization::class)->end()
                                                ->scalarNode('interface')->cannotBeEmpty()->defaultValue(OrganizationInterface::class)->end()
                                                ->scalarNode('repository')->defaultValue(OrganizationRepository::class)->end()
                                                ->scalarNode('factory')->defaultValue(OrganizationFactory::class)->end()
                                                ->scalarNode('object_manager_name')->defaultValue(null)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() // orm
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
