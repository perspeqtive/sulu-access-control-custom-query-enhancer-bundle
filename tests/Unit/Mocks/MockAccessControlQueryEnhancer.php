<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class MockAccessControlQueryEnhancer implements AccessControlQueryEnhancerInterface
{
    public bool $wasCalled = false;

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void
    {
        $this->wasCalled = true;
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        $this->wasCalled = true;
    }
}
