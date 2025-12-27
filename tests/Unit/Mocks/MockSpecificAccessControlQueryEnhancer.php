<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks;

use Doctrine\ORM\QueryBuilder;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer\SpecificAccessControlQueryEnhancerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class MockSpecificAccessControlQueryEnhancer implements SpecificAccessControlQueryEnhancerInterface
{
    public bool $wasCalled = false;

    public bool $isResponsible = false;

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void
    {
        $this->wasCalled = true;
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        $this->wasCalled = true;
    }

    public function supports(string $class): bool
    {
        return $this->isResponsible;
    }
}
