<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Security\Authentication\UserInterface;

interface SpecificAccessControlQueryEnhancerInterface
{
    /**
     * @param class-string $entityClass
     */
    public function enhance(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityIdField = 'id',
    ): void;

    /**
     * @param class-string $entityClass
     */
    public function enhanceWithDynamicEntityClass(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityClassField,
        string $entityIdField,
    ): void;

    /**
     * @param class-string $class
     */
    public function supports(string $class): bool;
}
