<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

readonly class AccessControlQueryEnhancer implements AccessControlQueryEnhancerInterface
{
    public function __construct(
        private iterable $queryEnhancers,
        private AccessControlQueryEnhancerInterface $defaultQueryEnhancer,
    ) {}

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void
    {
        foreach ($this->queryEnhancers as $queryEnhancer) {
            if (false === $queryEnhancer->supports($entityClass)) {
                continue;
            }
            $queryEnhancer->enhance($queryBuilder, $user, $permission, $entityClass, $entityAlias);

            return;
        }
        $this->defaultQueryEnhancer->enhance($queryBuilder, $user, $permission, $entityClass, $entityAlias);
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        foreach ($this->queryEnhancers as $queryEnhancer) {
            if (false === $queryEnhancer->supports($entityClass)) {
                continue;
            }
            $queryEnhancer->enhanceWithDynamicEntityClass($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);

            return;
        }
        $this->defaultQueryEnhancer->enhanceWithDynamicEntityClass($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);
    }
}
