<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\QueryEnhancer;

use Doctrine\ORM\QueryBuilder;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer\AccessControlQueryEnhancer;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks\MockAccessControlQueryEnhancer;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Unit\Mocks\MockSpecificAccessControlQueryEnhancer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class AccessControlQueryEnhancerTest extends TestCase
{
    private MockSpecificAccessControlQueryEnhancer $specificEnhancer1;
    private MockSpecificAccessControlQueryEnhancer $specificEnhancer2;
    private MockAccessControlQueryEnhancer $coreEnhancer;

    protected function setUp(): void
    {
        $this->specificEnhancer1 = new MockSpecificAccessControlQueryEnhancer();
        $this->specificEnhancer2 = new MockSpecificAccessControlQueryEnhancer();
        $this->coreEnhancer = new MockAccessControlQueryEnhancer();
        $this->enhancer = new AccessControlQueryEnhancer(
            [$this->specificEnhancer1, $this->specificEnhancer2],
            $this->coreEnhancer
        );
    }

    public function testEnhancerFailOverToDefaultWhenNoneResponsible(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhance($queryBuilder, null, 0, 'foo', 'bar');

        self::assertTrue($this->coreEnhancer->wasCalled);
        self::assertFalse($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerOnlyCallsResponsibleEnhancer(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhance($queryBuilder, null, 0, 'foo', 'bar');

        self::assertFalse($this->coreEnhancer->wasCalled);
        self::assertTrue($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerFailOverToDefaultWhenNoneResponsibleOnExtendedQuery(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhanceWithDynamicEntityClass($queryBuilder, null, 0, 'foo', 'bar', 'field', 'idField');

        self::assertTrue($this->coreEnhancer->wasCalled);
        self::assertFalse($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }

    public function testEnhancerOnlyCallsResponsibleEnhancerOnExtendedQuery(): void
    {
        $this->specificEnhancer1->isResponsible = true;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->enhancer->enhanceWithDynamicEntityClass($queryBuilder, null, 0, 'foo', 'bar', 'field', 'idField');

        self::assertFalse($this->coreEnhancer->wasCalled);
        self::assertTrue($this->specificEnhancer1->wasCalled);
        self::assertFalse($this->specificEnhancer2->wasCalled);
    }
}
