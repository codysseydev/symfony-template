---
name: new-module
description: >-
  Scaffold a brand-new {{PRODUCT_NAME}} bounded context (module): the module
  folder, its services file, its routes file, its Migrations folder and its entry
  in modules.php. Not for adding a feature to an existing module (see new-slice).
  The isolation rules a new module must follow are in .ai/rules/.
---

# Creating a New Module

## Pre-flight

This skill needs `/domain-design` output as input: the context name, its slice
list, and the glossary delta. A new module without it is a placement decision
made by accident. Run `/domain-design` first if it has not run for this change.

Read `docs/ARCHITECTURE.md` and `.ai/rules/index.md`. A module talks to other
modules through contracts and domain events only: no cross-module imports, no
Doctrine relations across modules, plain id columns with no foreign key for
cross-module references. `tests/Architecture/ModuleIsolationRules.php` enforces
the imports.

## Module structure

```
{{PRODUCT_NAMESPACE}}/<Module>/
  <module>-services.php     ContainerConfigurator closure
  <module>-routes.php       RoutingConfigurator closure
  Migrations/               this module's migrations (keep a .gitkeep)
  <Action>/                 vertical slice (see /new-slice)
    Tests/
  <Module>Exception.php     abstract base exception (see /exceptions)
```

`<module>` is the module name in lowercase. The namespace mirrors the directory:
`{{PRODUCT_NAMESPACE}}\<Module>\<Action>`. Group actions into concern folders
(`Lifecycle/`, `Queries/`) only once the module passes about ten action folders.

## The two config files

Copy them from an existing module, for example `Health`, and change the module name.

`<module>-services.php` sets the parameter `module.<module>` (the module list
test reads it to prove the kernel loaded the file) and loads the module folder as
autowired services, excluding `Migrations/` and every `Tests/` folder.

`<module>-routes.php` imports the module folder with the `attribute` type,
excluding `Migrations/` and every `Tests/` folder.

## Register the module

1. Add the module name to `modules.php`. A module that is not in the list is not
   loaded, mapped, migrated or checked.
2. Create the two config files and the `Migrations/` folder. A migration
   namespace `{{PRODUCT_NAMESPACE}}\<Module>\Migrations` is configured from the
   list.
3. Update the module section of `docs/ARCHITECTURE.md` if it names the modules,
   and add the module's key terms to the glossary in the `{{PRODUCT_SLUG}}-docs`
   brain vault.

## Checklist

- [ ] Module folder created under `{{PRODUCT_NAMESPACE}}/`
- [ ] `<module>-services.php`, `<module>-routes.php` and `Migrations/` created
- [ ] Module added to `modules.php`
- [ ] Isolation honored: no cross-module imports or relations, no container
      injection in product code
- [ ] Tests live in `Tests/` inside each action folder, and the module has one
      test that boots the kernel and reads `module.<module>`
- [ ] `/preflight` passes (`ModuleListTest` covers the list, the files and the
      kernel loading them)
