# {{PRODUCT_NAME}} Architecture

{{PRODUCT_PITCH}}

The product's decisions, glossary, brief and event model live in the `{{PRODUCT_SLUG}}-docs` brain vault. This file covers only how the code is laid out.

## Layout

```
{{PRODUCT_NAMESPACE}}/
  <Module>/          one bounded context per module
Framework/           shared infrastructure: the Kernel, the buses, the schedule, no product rules
config/              bundle config and the glue that reads modules.php
tests/Architecture/  architecture tests (module isolation, class shape, console command naming, exceptions, container access)
tools/phpstan/       PHPStan extensions
```

There is no `src/` folder. A module's namespace is `{{PRODUCT_NAMESPACE}}\<Module>` and mirrors its directory path. Migrations live in each module.

## Modules

A module is a bounded context. It owns its entities, migrations, slices and tests. Another module reaches it only through contracts and domain events, never through its classes, relations or tables (`.ai/rules/`).

`modules.php` is the one list of modules. The service config, the route config, the Doctrine mapping, the migrations config, PHPArkitect, PHPStan, Rector and PHP CS Fixer all read it. Add a module with `/new-module`.

A module has four fixed parts:

```
{{PRODUCT_NAMESPACE}}/<Module>/
  <module>-services.php   ContainerConfigurator closure, imported by config/services.php
  <module>-routes.php     RoutingConfigurator closure, imported by config/routes.php
  Migrations/             this module's Doctrine migrations
  <Action>/               one folder per slice
```

## Slices

One action is one folder holding every piece it needs:

```
{{PRODUCT_NAMESPACE}}/<Module>/<Action>/
  <Action>Command.php          write slices; read slices use <Action>Query.php
  <Action>CommandHandler.php   orchestrates, holds no business logic
  <Action>Controller.php       single-action __invoke() with a #[Route]
  <Action>Request.php          input DTO read with #[MapRequestPayload]
  <Action>Resource.php         maps the result to an array for JsonResponse
  <Action>Event.php            past-tense fact, only when state changes
  Tests/                       tests live beside the code
```

Entities, enums and exceptions sit in the slice or entity concept folder that owns them, never in type folders such as `Entity/` or `Enums/`.

## Buses

`Framework\Bus\CommandBus::dispatch()` sends a command through `command.bus`, which runs the handler in a Doctrine transaction. `Framework\Bus\QueryBus::ask()` sends a query through `query.bus` and returns the handler's result. A handler declares its bus with `#[AsMessageHandler(bus: 'command.bus')]` or `#[AsMessageHandler(bus: 'query.bus')]`. Controllers depend on the two bus classes, never on `MessageBusInterface`.

## Gates

The checks every change must pass are listed in `.factory/recipe.yml`, and they run in Docker. Run them with `/preflight`.
