---
name: exceptions
description: >-
  Add a domain exception to a {{PRODUCT_NAME}} module: the module base exception,
  a specific exception with a named constructor, and the right HTTP status through
  #[WithHttpStatus].
---

# Domain Exception Hierarchy

Symfony turns an exception into a response when the exception carries a status.
Controllers never need try-catch.

## Pattern

Each module has an abstract base exception that extends `DomainException` and
carries `#[WithHttpStatus]`. Specific exceptions extend the base. They inherit its
status or declare their own.

```
<Module>Exception (abstract, #[WithHttpStatus(409)])
  EntityNotFoundException (#[WithHttpStatus(404)])
  InvalidStateException (inherits 409)
  NotAuthorizedException (#[WithHttpStatus(403)])
```

`tests/Architecture/ExceptionRules.php` requires every `*Exception` in the product
namespace to carry the attribute, inherit it from a parent, or implement
`HttpExceptionInterface`. `RuntimeException` descendants are exempt: they mark a
queue or schedule invariant that never answers a request and should be reported.

## Module base exception

Create it at the module root, next to the config files:

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
abstract class OrdersException extends \DomainException {}
```

The default is 409 Conflict, which fits most state violations such as already
cancelled or duplicate purchase.

## Specific exceptions

Place each in the slice or entity concept folder it relates to, and build it
through a named constructor:

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders\Order;

use {{PRODUCT_NAMESPACE}}\Orders\OrdersException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(404)]
final class OrderNotFoundException extends OrdersException
{
    public static function forId(int $id): self
    {
        return new self(sprintf('The order with ID %d was not found.', $id));
    }
}
```

`CommandBus` rethrows a handler's exception unwrapped, and `QueryBus` does the
same through `HandleTrait`, so the attribute is read from your exception and not
from Messenger's `HandlerFailedException`.

## HTTP statuses

| Status | When | Example |
|--------|------|---------|
| 404 | Entity not found | `OrderNotFoundException::forId($id)` |
| 403 | Authorization failure | `NotOrderOwnerException` |
| 409 | State conflict (default) | `OrderAlreadyCancelledException` |
| 422 | Business rule validation | `SeatLimitReachedException` |

## Rules

- The base exception lives at the module root; specific exceptions live in the
  slice or entity concept folder.
- Always use a named constructor. Never `throw new \Exception('message')`.
- Every business-rule exception has a test that asserts the rendered HTTP status
  at the request level. A test that only calls `expectException()` cannot catch a
  wrong status code.
- An expected domain exception answering an HTTP client is a 4xx, not an incident.
  The same exception killing a queued message or a schedule run is an incident and
  must still be reported.
