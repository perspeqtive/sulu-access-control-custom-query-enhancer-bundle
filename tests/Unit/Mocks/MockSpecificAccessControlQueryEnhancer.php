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

    /**
     * @var array<string, string>
     */
    public array $receivedArguments = [];

    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityIdField = 'id'): void
    {
        $this->wasCalled = true;
        $this->receivedArguments = [
            'entityClass' => $entityClass,
            'entityAlias' => $entityAlias,
            'entityIdField' => $entityIdField,
        ];
    }

    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void
    {
        $this->wasCalled = true;
        $this->receivedArguments = [
            'entityClass' => $entityClass,
            'entityAlias' => $entityAlias,
            'entityClassField' => $entityClassField,
            'entityIdField' => $entityIdField,
        ];
    }

    public function supports(string $class): bool
    {
        return $this->isResponsible;
    }
}
