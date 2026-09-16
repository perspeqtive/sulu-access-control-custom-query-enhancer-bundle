<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer\AccessControlQueryEnhancer;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\SuluAccessControlCustomQueryEnhancerBundle;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Fixtures\ConcreteEnhancerConsumer;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer as SuluAccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Symfony\Component\DependencyInjection\Compiler\CheckTypeDeclarationsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ServiceConfigurationTest extends TestCase
{
    public function testBundleDecoratesSuluEnhancerAndContainerCompiles(): void
    {
        $container = $this->buildContainer();
        $container->compile();

        $definition = $container->getDefinition('sulu_security.access_control_query_enhancer');

        self::assertSame(AccessControlQueryEnhancer::class, $definition->getClass());

        $default = $definition->getArgument(1);
        $defaultClass = $default instanceof Definition
            ? $default->getClass()
            : $container->getDefinition((string) $default)->getClass();

        self::assertSame(SuluAccessControlQueryEnhancer::class, $defaultClass);
    }

    public function testDecoratedServiceSatisfiesConcreteTypeHints(): void
    {
        $container = $this->buildContainer();
        $container->register('test.concrete_enhancer_consumer', ConcreteEnhancerConsumer::class)
            ->setPublic(true)
            ->setArguments([new Reference('sulu_security.access_control_query_enhancer')])
        ;
        $container->addCompilerPass(new CheckTypeDeclarationsPass(true), PassConfig::TYPE_AFTER_REMOVING, -100);

        $container->compile();

        self::assertTrue($container->hasDefinition('test.concrete_enhancer_consumer'));
    }

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', \sys_get_temp_dir());

        $container->setDefinition('sulu_security.system_store', new Definition(SystemStoreInterface::class));
        $container->setDefinition('doctrine.orm.entity_manager', new Definition(EntityManagerInterface::class));
        $container->register('sulu_security.access_control_query_enhancer', SuluAccessControlQueryEnhancer::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('sulu_security.system_store'),
                new Reference('doctrine.orm.entity_manager'),
            ])
        ;

        $bundle = new SuluAccessControlCustomQueryEnhancerBundle();
        $extension = $bundle->getContainerExtension();
        self::assertNotNull($extension);
        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias());

        return $container;
    }
}
