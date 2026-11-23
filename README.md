# Symfony template

Template repository for the software factory. `scripts/new-repo.py` copies `template/` into a new product repository: PHP 8.5, Symfony 8.1, modules as root namespaces (no `src/` folder), one sample slice, and five gates that run in Docker.

## Make a repository

```
python3 scripts/new-repo.py --name "Sample Product" --slug sample-product \
    --pitch "One line." --target ../sample-product --modules Orders,Billing
```

The target must be missing or empty. The script fills the tokens, refuses to finish if any `{{TOKEN}}` is left, runs `git init` and makes the first commit. Exit code 2 means bad input.

| Flag | Meaning |
|------|---------|
| `--name` | Product name. No double quote, backslash or dollar sign. |
| `--slug` | Lowercase letters, digits and hyphens. Names the Compose project `<slug>-factory`. |
| `--pitch` | One line. |
| `--target` | Directory to create. |
| `--namespace` | PHP namespace and folder for the product modules. Default: StudlyCase of `--name`. |
| `--modules` | Comma-separated StudlyCase names. Each gets an empty module: a services file, a routes file, a `Migrations/` folder and one passing test. |
| `--date` | `YYYY-MM-DD`, default today. |
| `--no-git` | Skip `git init` and the first commit. |

`--namespace` and `--modules` reject `Framework`, `Tests`, `Tools`, `App`, `Symfony`, `Doctrine`, `Bin`, `Config`, `Public`, `Var`, `Docs`, `Openspec` and `Vendor`. `--modules` also rejects `Health`, which the template already has.

## Tokens

| Token | Filled with |
|-------|-------------|
| `{{PRODUCT_NAME}}` | `--name` |
| `{{PRODUCT_SLUG}}` | `--slug` |
| `{{PRODUCT_PITCH}}` | `--pitch` |
| `{{PRODUCT_NAMESPACE}}` | `--namespace` |
| `{{DATE}}` | `--date` |

Folder and file names may carry tokens. `module-template/` is the stub for one `--modules` entry and uses `{{MODULE}}` and `{{MODULE_LOWER}}`.

## What the generated repository has

- `modules.php`: the one list of modules. The service config, the route config, the Doctrine mapping and migrations config, PHPArkitect, PHPStan, Rector and PHP CS Fixer read it. PHPUnit scans the namespace folder, and `ModuleListTest` fails when the folder and the list disagree, when a module lacks its files, or when the kernel does not load them.
- A module is a folder with `<module>-services.php`, `<module>-routes.php`, `Migrations/` and its slices. Entities live in the slice that owns them.
- `Health/GetStatus`: the sample slice. A query, its handler on `query.bus`, a controller that asks `Framework\Bus\QueryBus`, a resource and a functional test that needs MySQL.
- `Framework/`: the kernel, `CommandBus` (over `command.bus`, with the `doctrine_transaction` middleware), `QueryBus` (over `query.bus`), an empty schedule, and a compiler pass that keeps `Tests/` folders out of the Doctrine mapping scan, so a production install without dev packages loads.
- `.factory/recipe.yml`: setup, checks and teardown. Checks run in Docker with a dedicated Compose file and project name, publish no host ports and do not touch a running dev stack.
- Gates: `php-cs-fixer fix --dry-run` (`@Symfony`, then `@PER-CS3x0`, imports sorted alphabetically), `phparkitect check`, `phpstan` at level max with phpstan-symfony and phpstan-doctrine, `rector --dry-run`, `phpunit` (suites Modules, Framework, Architecture). `composer gates` runs all five.
- Architecture rules: module isolation, framework isolation, class shape (controllers, handlers, commands, queries, events), console command naming (`#[AsCommand]` classes end in `ConsoleCommand`), exceptions (every product `*Exception` carries `#[WithHttpStatus]` or implements `HttpExceptionInterface`) and no container injection in product code.
- Dev stack: `docker-compose.yml` plus `docker-compose.dev.yml` (Traefik, PHP, MySQL 8.4, Redis, Mailpit, a `messenger:consume async` worker, a scheduler), started with `spin up`.
- GitHub Actions for the five gates, under `template/.github/workflows/`.

## Test the template

```
python3 -m unittest discover -s tests
```

These tests build sample repositories and inspect them, and test the import sorter. They do not need Docker.

The gate run builds three repositories (namespaces that sort before Doctrine, between Framework and Symfony, and after Tests) and runs the whole recipe on each. It needs Docker and about three minutes:

```
SYMFONY_TEMPLATE_GATES=1 python3 -m unittest tests.test_gates -v
```

`new-repo.py` re-sorts the `use` block of every PHP file after filling tokens, matching PHP CS Fixer's `ordered_imports` (alpha, class then function then const), because a namespace token changes the sort order per product. The comparison copies `OrderedImportsFixer::sortAlphabetically`.

## Refresh `composer.lock`

`template/composer.lock` is committed so a fresh clone installs the same versions. `new-repo.py` rewrites its `content-hash` for the new package name. After changing `template/composer.json`, run `composer update` in a generated repository and copy its `composer.lock` back.

## Rule copies

The files in `template/.ai/rules/` (except `index.md`) are copies of the Kanon references in [codysseydev/codyssey-skills](https://github.com/codysseydev/codyssey-skills) under `kanon/`. Each copy is a `paths:` header plus the source body. `scripts/rules-manifest.json` lists every copy with its source path and globs.

```
python3 scripts/sync-rules.py          # rewrite the copies from codyssey-skills main
python3 scripts/sync-rules.py --check  # write nothing, exit 1 and list every copy that drifted
```

Without `--skills <dir>` or `KANON_SKILLS_DIR`, the script keeps a shallow clone in `.cache/codyssey-skills` (gitignored). `tests/test_rules_drift.py` runs `--check`, so the unit tests fail when a copy falls behind and need network access. Adding a rule means adding a manifest entry, running the sync and adding a row to `template/.ai/rules/index.md`.

## Factory recipe for this repository

`.factory/recipe.yml` runs the template's own checks: `unit`, `drift` and `gates`. The teardown removes the three Compose projects of the gate run in case a run was killed. `.factory/playbook.md` is the project file the factory reads.
