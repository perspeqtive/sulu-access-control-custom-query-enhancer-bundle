<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Decorates Sulu's access control query enhancer and hands the query over to the first
 * specific enhancer that supports the given entity class. If no specific enhancer supports
 * it, the decorated Sulu enhancer keeps handling the query as before.
 */
class AccessControlQueryEnhancer implements AccessControlQueryEnhancerInterface
{
    /**
     * @param iterable<SpecificAccessControlQueryEnhancerInterface> $queryEnhancers
     */
    public function __construct(
        private readonly iterable $queryEnhancers,
        private readonly AccessControlQueryEnhancerInterface $defaultQueryEnhancer,
    ) {
    }

    /**
     * @param class-string $entityClass
     */
    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityIdField = 'id'): void
    {
        $queryEnhancer = $this->findQueryEnhancer($entityClass);
        if (null === $queryEnhancer) {
            $this->defaultQueryEnhancer->enhance($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityIdField);

            return;
        }

        $queryEnhancer->enhance($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityIdField);
    }

    /**
     * @param class-string $entityClass
     */
    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        $queryEnhancer = $this->findQueryEnhancer($entityClass);
        if (null === $queryEnhancer) {
            $this->defaultQueryEnhancer->enhanceWithDynamicEntityClass($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);

            return;
        }

        $queryEnhancer->enhanceWithDynamicEntityClass($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);
    }

    /**
     * @param class-string $entityClass
     */
    private function findQueryEnhancer(string $entityClass): ?SpecificAccessControlQueryEnhancerInterface
    {
        foreach ($this->queryEnhancers as $queryEnhancer) {
            if (false === $queryEnhancer->supports($entityClass)) {
                continue;
            }

            return $queryEnhancer;
        }

        return null;
    }
}
