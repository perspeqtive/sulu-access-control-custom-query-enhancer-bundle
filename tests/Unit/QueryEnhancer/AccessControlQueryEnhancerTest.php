<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\QueryEnhancer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer\AccessControlQueryEnhancer;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks\MockAccessControlQueryEnhancer;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks\MockSpecificAccessControlQueryEnhancer;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer as SuluAccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;

class AccessControlQueryEnhancerTest extends TestCase
{
    private MockSpecificAccessControlQueryEnhancer $specificEnhancer1;
    private MockSpecificAccessControlQueryEnhancer $specificEnhancer2;
    private MockAccessControlQueryEnhancer $coreEnhancer;
    private AccessControlQueryEnhancer $enhancer;

    protected function setUp(): void
    {
        $this->specificEnhancer1 = new MockSpecificAccessControlQueryEnhancer();
        $this->specificEnhancer2 = new MockSpecificAccessControlQueryEnhancer();
        $this->coreEnhancer = new MockAccessControlQueryEnhancer();
        $this->enhancer = new AccessControlQueryEnhancer(
            [$this->specificEnhancer1, $this->specificEnhancer2],
            $this->coreEnhancer,
            self::createStub(SystemStoreInterface::class),
            self::createStub(EntityManagerInterface::class),
        );
    }

    public function testEnhancerFailOverToDefaultWhenNoneResponsible(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhance($queryBuilder, null, 0, \stdClass::class, 'bar');

        self::assertTrue($this->coreEnhancer->wasCalled);
        self::assertFalse($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerOnlyCallsResponsibleEnhancer(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhance($queryBuilder, null, 0, \stdClass::class, 'bar');

        self::assertFalse($this->coreEnhancer->wasCalled);
        self::assertTrue($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerFailOverToDefaultWhenNoneResponsibleOnExtendedQuery(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhanceWithDynamicEntityClass($queryBuilder, null, 0, \stdClass::class, 'bar', 'field', 'idField');

        self::assertTrue($this->coreEnhancer->wasCalled);
        self::assertFalse($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerOnlyCallsResponsibleEnhancerOnExtendedQuery(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhanceWithDynamicEntityClass($queryBuilder, null, 0, \stdClass::class, 'bar', 'field', 'idField');

        self::assertFalse($this->coreEnhancer->wasCalled);
        self::assertTrue($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerPassesAllArgumentsToResponsibleEnhancer(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhance($queryBuilder, null, 0, \stdClass::class, 'bar', 'uuid');

        self::assertSame(
            ['entityClass' => \stdClass::class, 'entityAlias' => 'bar', 'entityIdField' => 'uuid'],
            $this->specificEnhancer1->receivedArguments,
        );
    }

    public function testEnhancerPassesAllArgumentsToResponsibleEnhancerOnExtendedQuery(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhanceWithDynamicEntityClass($queryBuilder, null, 0, \stdClass::class, 'bar', 'field', 'idField');

        self::assertSame(
            [
                'entityClass' => \stdClass::class,
                'entityAlias' => 'bar',
                'entityClassField' => 'field',
                'entityIdField' => 'idField',
            ],
            $this->specificEnhancer1->receivedArguments,
        );
    }

    public function testEnhancerForwardsEntityIdFieldToSuluDefaultEnhancer(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $suluEnhancer = $this->createMock(SuluAccessControlQueryEnhancer::class);
        $suluEnhancer->expects(self::once())
            ->method('enhance')
            ->with($queryBuilder, null, 0, \stdClass::class, 'bar', 'uuid')
        ;

        $enhancer = new AccessControlQueryEnhancer(
            [$this->specificEnhancer1],
            $suluEnhancer,
            self::createStub(SystemStoreInterface::class),
            self::createStub(EntityManagerInterface::class),
        );
        $enhancer->enhance($queryBuilder, null, 0, \stdClass::class, 'bar', 'uuid');
    }

    public function testEnhancerIsAssignableToSuluEnhancer(): void
    {
        self::assertInstanceOf(SuluAccessControlQueryEnhancer::class, $this->enhancer);
    }
}
