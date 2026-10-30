<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\Tests\Fixtures;

use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;

/**
 * Mirrors Sulu's MediaSmartContentProvider, which type hints AccessControlQueryEnhancerInterface.
 */
class EnhancerConsumer
{
    public function __construct(
        public readonly AccessControlQueryEnhancerInterface $accessControlQueryEnhancer,
    ) {}
}
