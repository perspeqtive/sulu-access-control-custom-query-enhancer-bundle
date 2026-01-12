<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\MappingException;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\System\SystemStore;
use Sulu\Component\Security\Authentication\UserInterface;

readonly class DefaultAccessControlQueryEnhancer
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SystemStore            $systemStore,
    )
    {
    }

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void
    {
        if ($user instanceof UserInterface === false) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if ($this->isAdminUser($user) === true) {
            return;
        }

        $this->enhanceMainQuery($queryBuilder, $user, $permission, $entityClass, $entityAlias);
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        if ($user instanceof UserInterface === false) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if ($this->isAdminUser($user) === true) {
            return;
        }
        $this->enhanceMainQuery($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);
    }

    private function isAdminUser(UserInterface $user): bool
    {
        $roles = $user->getRoleObjects();
        foreach ($roles as $role) {
            $key = $role->getKey();
            if ($key === null) {
                continue;
            }
            if (in_array($key, ['admin'], true) === true) {
                return true;
            }
        }

        return false;
    }

    private function enhanceMainQuery(
        QueryBuilder   $queryBuilder,
        ?UserInterface $user,
        int            $permission,
        string         $entityClass,
        string         $entityAlias,
        string         $entityIdField = 'id',
        ?string        $entityClassField = null): void
    {
        $subQueryBuilder = $this->getAllowedItemsDql($entityClass, $entityIdField, $entityClassField);
        $notAllowedItemsQueryBuilder = $this->getNotAllowedQueryBuilder($entityClass, $subQueryBuilder, $user, $permission, $entityClassField);

        $result = $notAllowedItemsQueryBuilder->getQuery()->getScalarResult();
        $ids = array_column($result, 'id');

        if (count($ids) > 0) {
            $queryBuilder->andWhere(sprintf('%s.id NOT IN (:accessControlIds)', $entityAlias));
            $queryBuilder->setParameter('accessControlIds', $ids);
        }
    }

    private function getUserRoleIds(?UserInterface $user): array
    {
        if ($user) {
            $roleIds = [];

            foreach ($user->getRoleObjects() as $role) {
                if ($role->getSystem() === $this->systemStore->getSystem()) {
                    $roleIds[] = $role->getId();
                }
            }

            return $roleIds;
        }

        $anonymousRole = $this->systemStore->getAnonymousRole();

        return $anonymousRole ? [$anonymousRole->getId()] : [];
    }

    private function getAllowedItemsDql(string $entityClass, string $entityIdField = 'id', ?string $entityClassField = null): QueryBuilder
    {
        $accessClassCondition = 'accessControl.entityClass = :entityClass';
        if ($entityClassField) {
            $accessClassCondition = 'accessControl.entityClass = entity.' . $entityClassField;
        }

        $subQueryBuilder = $this->entityManager->createQueryBuilder();
        $subQueryBuilder->select()
            ->from($entityClass, 'allowedEntity')
            ->addSelect('1')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                $accessClassCondition . ' AND ' . $this->getEntityIdCondition($entityClass, 'entity', $entityIdField)
            )
            ->where(
                $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->andX(
                        'accessControl.role IN (:roleIds)',
                        'BIT_AND(accessControl.permissions, :permission) = :permission',
                    ),
                    'accessControl.id IS NULL',
                ),
            );
        $subQueryBuilder->andWhere('allowedEntity.id = entity.id');

        return $subQueryBuilder;
    }

    private function getNotAllowedQueryBuilder(string $entityClass, QueryBuilder $subQueryBuilder, ?UserInterface $user, int $permission, ?string $entityClassField = null): QueryBuilder
    {
        $notAllowedItemsQueryBuilder = $this->entityManager->createQueryBuilder();
        $notAllowedItemsQueryBuilder->select('entity.id')
            ->from($entityClass, 'entity');

        $notAllowedItemsQueryBuilder->where(
            $notAllowedItemsQueryBuilder->expr()->not(
                $notAllowedItemsQueryBuilder->expr()->exists($subQueryBuilder->getDQL()),
            ),
        );
        $notAllowedItemsQueryBuilder->setParameter('entityClass', $entityClass)
            ->setParameter('roleIds', $this->getUserRoleIds($user))
            ->setParameter('permission', $permission);

        if ($entityClassField !== null) {
            $notAllowedItemsQueryBuilder->setParameter('entityClassField', $entityClassField);
        }

        return $notAllowedItemsQueryBuilder;
    }

    private function getEntityIdCondition(string $entityClass, string $entityAlias, string $entityIdField = 'id'): string
    {
        $entityIdCondition = 'accessControl.entityId = ' . $entityAlias . '.' . $entityIdField;
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ('integer' === $metadata->getTypeOfField($entityIdField)) {
                $entityIdCondition = 'accessControl.entityIdInteger = ' . $entityAlias . '.' . $entityIdField;
            }
        } catch (MappingException $e) {
            $metadata = null;
        }

        return $entityIdCondition;
    }
}