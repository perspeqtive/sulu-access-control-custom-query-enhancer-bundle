<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Fixtures;

use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;

/**
 * Mirrors Sulu's MediaSmartContentProvider, which type hints the concrete
 * AccessControlQueryEnhancer instead of AccessControlQueryEnhancerInterface.
 */
class ConcreteEnhancerConsumer
{
    public function __construct(
        public readonly AccessControlQueryEnhancer $accessControlQueryEnhancer,
    ) {}
}
