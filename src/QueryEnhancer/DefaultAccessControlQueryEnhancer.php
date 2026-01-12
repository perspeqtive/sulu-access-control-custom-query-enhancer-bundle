<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\MappingException;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\System\SystemStore;
use Sulu\Component\Security\Authentication\UserInterface;

readonly class DefaultAccessControlQueryEnhancer implements AccessControlQueryEnhancerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SystemStore $systemStore,
    ) {}

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void
    {
        if (false === $user instanceof UserInterface) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if (true === $this->isAdminUser($user)) {
            return;
        }

        $this->enhanceMainQuery($queryBuilder, $user, $permission, $entityClass, $entityAlias);
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        if (false === $user instanceof UserInterface) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if (true === $this->isAdminUser($user)) {
            return;
        }
        $this->enhanceMainQuery($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityClassField, $entityIdField);
    }

    private function isAdminUser(UserInterface $user): bool
    {
        $roles = $user->getRoleObjects();
        foreach ($roles as $role) {
            $key = $role->getKey();
            if (null === $key) {
                continue;
            }
            if (true === in_array($key, ['admin'], true)) {
                return true;
            }
        }

        return false;
    }

    private function enhanceMainQuery(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityIdField = 'id',
        ?string $entityClassField = null
    ): void {
        $subQueryBuilder = $this->getAllowedItemsDql($entityClass, $entityIdField, $entityClassField);
        $notAllowedItemsQueryBuilder = $this->getNotAllowedQueryBuilder($entityClass, $subQueryBuilder, $user, $permission, $entityClassField);

        try {
            $result = $notAllowedItemsQueryBuilder->getQuery()->getScalarResult();
            $ids = array_column($result, 'id');
            if (count($ids) > 0) {
                $queryBuilder->andWhere(sprintf('%s.id NOT IN (:accessControlIds)', $entityAlias));
                $queryBuilder->setParameter('accessControlIds', $ids);
            }
        } catch (\Exception $e) {

        }
    }

    private function getAllowedItemsDql(string $entityClass, string $entityIdField = 'id', ?string $entityClassField = null): QueryBuilder
    {
        $accessClassCondition = 'accessControl.entityClass = :entityClass';
        if ($entityClassField) {
            $accessClassCondition = 'accessControl.entityClass = entity.'.$entityClassField;
        }

        $subQueryBuilder = $this->entityManager->createQueryBuilder();
        $subQueryBuilder->select()
            ->from($entityClass, 'allowedEntity')
            ->addSelect('1')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                $accessClassCondition.' AND '.$this->getEntityIdCondition($entityClass, 'entity', $entityIdField)
            )
            ->where(
                $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->andX(
                        'accessControl.role IN (:roleIds)',
                        'BIT_AND(accessControl.permissions, :permission) = :permission',
                    ),
                    'accessControl.id IS NULL',
                ),
            )
        ;
        $subQueryBuilder->andWhere('allowedEntity.id = entity.id');

        return $subQueryBuilder;
    }

    private function getNotAllowedQueryBuilder(string $entityClass, QueryBuilder $subQueryBuilder, ?UserInterface $user, int $permission, ?string $entityClassField = null): QueryBuilder
    {
        $notAllowedItemsQueryBuilder = $this->entityManager->createQueryBuilder();
        $notAllowedItemsQueryBuilder->select('entity.id')
            ->from($entityClass, 'entity')
        ;

        $notAllowedItemsQueryBuilder->where(
            $notAllowedItemsQueryBuilder->expr()->not(
                $notAllowedItemsQueryBuilder->expr()->exists($subQueryBuilder->getDQL()),
            ),
        );
        $this->applyParameters($notAllowedItemsQueryBuilder, $entityClass, $user, $permission, $entityClassField);

        return $notAllowedItemsQueryBuilder;
    }

    private function getEntityIdCondition(string $entityClass, string $entityAlias, string $entityIdField = 'id'): string
    {
        $entityIdCondition = 'accessControl.entityId = '.$entityAlias.'.'.$entityIdField;

        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ('integer' === $metadata->getTypeOfField($entityIdField)) {
                $entityIdCondition = 'accessControl.entityIdInteger = '.$entityAlias.'.'.$entityIdField;
            }
        } catch (MappingException $e) {
            $metadata = null;
        }

        return $entityIdCondition;
    }

    private function applyParameters(QueryBuilder $notAllowedItemsQueryBuilder, string $entityClass, ?UserInterface $user, int $permission, ?string $entityClassField): void
    {
        if (null !== $entityClassField) {
            $notAllowedItemsQueryBuilder->setParameter('roleIds', $this->getUserRoleIds($user))
                ->setParameter('permission', $permission)
            ;

            return;
        }
        $notAllowedItemsQueryBuilder->setParameter('entityClass', $entityClass)
            ->setParameter('roleIds', $this->getUserRoleIds($user))
            ->setParameter('permission', $permission)
        ;
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
}
