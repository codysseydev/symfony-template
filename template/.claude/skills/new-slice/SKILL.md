---
name: new-slice
description: >-
  Add a new feature endpoint (vertical slice) inside an existing {{PRODUCT_NAME}}
  module: the command or query, handler, controller, request, resource, event,
  and tests for one action. Controller files are skipped for slices dispatched
  from a console command, a schedule or a message handler. Not for creating a
  brand-new module (see new-module).
---

# Creating a New Vertical Slice

You are adding one action to an existing module. Follow every rule below. The
rules are in `.ai/rules/`; read the files that match the paths you will touch
before writing (`.ai/rules/index.md` lists them).

## Pre-flight

0. If the slice adds an aggregate, a cross-module interaction, or a term missing
   from the glossary in the `{{PRODUCT_SLUG}}-docs` brain vault, run
   `/domain-design` first. Otherwise proceed.
1. Read `docs/ARCHITECTURE.md` and the module's existing slices.
2. Identify the module that owns the capability. If the slice needs another
   module's classes to do its job, it is in the wrong place or the collaboration
   must become a contract or an event.
3. If the module has more than ten action folders, pick the domain concern
   group (for example `Lifecycle/`, `Queries/`, `Management/`).

## File placement

- Group files by what they belong to. An entity, its enum, its repository and its
  exceptions sit in the slice that owns them, or in an entity concept folder such
  as `{{PRODUCT_NAMESPACE}}/Billing/Subscription/` when several slices share them.
- Never create type folders: no `Enums/`, `Events/`, `Listeners/`, `Exceptions/`,
  `Entity/`, `Contracts/`.
- The module's `<module>-services.php`, `<module>-routes.php`, `Migrations/` and
  base exception stay at the module root.

## Slice structure (write operation)

```
{{PRODUCT_NAMESPACE}}/<Module>/<Action>/
  <Action>Command.php          final readonly, promoted props
  <Action>CommandHandler.php   final readonly, orchestrator only
  <Action>Controller.php       final readonly, single-action __invoke()
  <Action>Request.php          final readonly input DTO with validation attributes
  <Action>Resource.php         final readonly, maps the result to an array
  <Action>Event.php            past tense, for example OrderPlaced
  Tests/
    <Action>Test.php
```

For a read operation, replace Command and CommandHandler with Query and
QueryHandler. The query handler returns a result DTO such as `OrderSummary`.

## Slices with no HTTP surface

Skip the controller, request, resource and route when no route reaches the
command, for example when a console command, a schedule or another handler
dispatches it.

- Create: Command, CommandHandler, an Event when state changes, an exception only
  when the caller must tell a failure apart, and a handler test.
- Test: `Tests/<Action>CommandHandlerTest.php` calls the handler and asserts the
  state change and the event. There is no controller test because there is no
  endpoint.
- A console command is a separate class named `<Action>ConsoleCommand` with
  `#[AsCommand]` and an `__invoke()` method. It dispatches the command through
  `CommandBus`.

## Orchestrator pattern

Application-layer classes coordinate, they do not execute. A controller reads the
request, dispatches a command or asks a query, and returns a resource. A handler
resolves dependencies, calls entity methods and dispatches events. Business rules
and state transitions live on the entity. A handler never updates entity state
directly, creates related records inline, or calls an external service directly.

## Tell, don't ask

Push the decision into the object. Write `$subscription->recoverIfPastDue()` on the
entity, not `if ($sub->isPastDue()) { $sub->activate(); }` in the handler.

## Wiring

The module's `<module>-services.php` loads every class in the module folder as an
autowired, autoconfigured service, and `<module>-routes.php` imports the
`#[Route]` attributes of the controllers. A new slice needs no registration.

- A handler declares its bus: `#[AsMessageHandler(bus: 'command.bus')]` for a
  command, `#[AsMessageHandler(bus: 'query.bus')]` for a query. It has one handler
  per message and an `__invoke()` method.
- A controller depends on `Framework\Bus\CommandBus` or `Framework\Bus\QueryBus`,
  never on `MessageBusInterface` or the container.
- `QueryBus::ask()` is typed by `tools/phpstan/QueryBusReturnTypeExtension.php`: it
  resolves `<Query>Handler::__invoke()`, so name the handler after the query.

## Example

```php
<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Orders\PlaceOrder;

use Framework\Bus\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PlaceOrderController
{
    public function __construct(private CommandBus $commandBus) {}

    #[Route('/api/orders', name: 'orders.place', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PlaceOrderRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new PlaceOrderCommand($request->customerId));

        return new JsonResponse(status: Response::HTTP_ACCEPTED);
    }
}
```

Commands, queries and events are `final readonly` with promoted public properties
and carry ids and scalars only. Use `declare(strict_types=1)` in every file.

## After creating the slice

1. Write tests following the rules: happy path, failure paths, edge cases, and a
   request-level assertion for every rendered HTTP status. Functional tests extend
   `WebTestCase`.
2. If the slice adds a Doctrine entity, put it in the slice folder with
   `#[ORM\Entity]` and add a migration to the module's `Migrations/` folder.
3. Run `/preflight`.
