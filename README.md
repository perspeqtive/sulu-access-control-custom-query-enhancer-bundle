# SuluAccessControlCustomQueryEnhancerBundle
![Static Badge](https://img.shields.io/badge/sulu_compatibility-%5E2.6_%7C%7C_%5E3.0.11-DCE5E4?labelColor=112A46)

The **Sulu Access Control Custom Query Enhancer Bundle** lets you replace Sulu's permission filtering
with your own logic — per entity class. Sulu applies one single, hard-wired strategy to every secured
Doctrine entity. This bundle turns that strategy into an extension point: you register a query enhancer
for a specific entity class, and it decides how permissions are translated into the list query. Every
entity class you do not claim keeps being handled by Sulu exactly as before.

## 🚀 Features
- Register your own access control logic per entity class
- Hide entities completely instead of showing them with a "no permission" marker
- Check permissions against something other than the entity itself (e.g. its webspace)
- Falls back to Sulu's own enhancer for every entity class you do not claim
- No core patching — the bundle decorates `sulu_security.access_control_query_enhancer`

## 💡 Use cases

### 1. Hide entities completely instead of marking them as locked

Sulu keeps some secured records in the list even when the user has no permission for them, and only
renders them with a "no permission" icon — media collections are the prominent example (see
[Sulu Permission Aware Collections Bundle](https://github.com/perspeqtive/sulu-permission-aware-collections-bundle)).
That is the right default for a CMS, but not for every domain: a record whose mere existence is
confidential must not be visible at all.

With a specific enhancer for that entity class you add your own `WHERE` restriction, so records
without permission never reach the result set and therefore never reach the admin UI.

### 2. Check permissions on a related entity instead of the entity itself

Sulu resolves permissions through `AccessControl` rows that point at *this* entity's class and id.
Permissions that live somewhere else cannot be expressed that way.

Example: a custom entity belongs to a webspace. A user who is not allowed to see the webspace must not
see the entity either — but there is no `AccessControl` row linking the two. A specific enhancer can
join or filter over the webspace and apply the webspace permission to the entity's list query.

## 🛠️ Installation

### Install the bundle via Composer:

```bash
composer require perspeqtive/sulu-access-control-custom-query-enhancer-bundle
```

### Enable the bundle

Register it in your `config/bundles.php`:

```php
return [
// ...
    PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\SuluAccessControlCustomQueryEnhancerBundle::class => ['all' => true],
];
```

That is all the setup there is. Without a registered enhancer the bundle is a no-op: every query keeps
being handled by Sulu's own enhancer.

## 🛠️ Usage

### 1. Implement `SpecificAccessControlQueryEnhancerInterface`

`supports()` decides which entity class you take over. `enhance()` is used for lists of a single
secured entity class, `enhanceWithDynamicEntityClass()` for lists whose entity class is stored in a
column (Sulu calls it with the class and id field names).

```php
<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Event;
use Doctrine\ORM\QueryBuilder;
use PERSPEQTIVE\SuluAccessControlCustomQueryEnhancerBundle\QueryEnhancer\SpecificAccessControlQueryEnhancerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class EventAccessControlQueryEnhancer implements SpecificAccessControlQueryEnhancerInterface
{
    public function __construct(
        private readonly WebspacePermissionResolver $webspacePermissionResolver,
    ) {}

    public function supports(string $class): bool
    {
        return Event::class === $class;
    }

    public function enhance(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityIdField = 'id',
    ): void {
        if (!$user instanceof UserInterface) {
            // no user at all: hide everything instead of falling back to the anonymous role
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        // the permission is not stored on the event but derived from its webspace
        $queryBuilder->andWhere(\sprintf('%s.webspaceKey IN (:allowedWebspaceKeys)', $entityAlias));
        $queryBuilder->setParameter(
            'allowedWebspaceKeys',
            $this->webspacePermissionResolver->getAllowedWebspaceKeys($user, $permission),
        );
    }

    public function enhanceWithDynamicEntityClass(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityClassField,
        string $entityIdField,
    ): void {
        $this->enhance($queryBuilder, $user, $permission, $entityClass, $entityAlias, $entityIdField);
    }
}
```

### 2. Register it with the bundle's tag

```yaml
services:
    App\Security\EventAccessControlQueryEnhancer:
        class: App\Security\EventAccessControlQueryEnhancer
        tags:
            - { name: perspeqtive_sulu_access_control_custom_query_enhancer.query_enhancer }
```

### How the dispatch works

| Situation                                              | Who handles the query                       |
|:-------------------------------------------------------|:--------------------------------------------|
| A tagged enhancer returns `true` from `supports()`     | That enhancer — Sulu's logic is not applied |
| Several tagged enhancers support the same entity class | The first one wins, the rest are skipped    |
| No tagged enhancer supports the entity class           | Sulu's own `AccessControlQueryEnhancer`     |

⚠️ A specific enhancer **replaces** Sulu's logic for its entity class, it does not run in addition to
it. If you want Sulu's permission filtering *plus* your own restriction, reproduce the part you need or
restrict yourself to `enhanceWithDynamicEntityClass()`.



