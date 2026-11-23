# {{PRODUCT_NAME}}

{{PRODUCT_PITCH}}

Symfony application built as vertical slices. Product code lives in `{{PRODUCT_NAMESPACE}}/<Module>/`, one bounded context per module, with `Framework/` as shared infrastructure. Tests sit beside the code in `Tests/` folders; architecture tests are in `tests/Architecture/`. There is no `src/` folder and no `App\` namespace. See `docs/ARCHITECTURE.md`.

## Product brain

The product's decisions, glossary, brief and event model live in the `{{PRODUCT_SLUG}}-docs` brain vault. Read it before planning any change, and record new decisions there. This repo holds code and repo-local docs only.

## Standards live in .ai/rules/

Every coding standard is a path-scoped rule file in `.ai/rules/`, loaded automatically when a matching file is read (`.claude/rules` symlinks there). `.ai/rules/index.md` maps globs to rule files; consult it before editing anything if rules did not auto-load. Do not restate rules elsewhere; edit the rule file.

## Documentation (`/docs`)

- `ARCHITECTURE.md`: layout, modules, slice shape
- `decisions/`: repo-local ADRs (product decisions are in the brain vault)

## Skills (procedures; auto-fire or `/name`)

| Skill | Use |
|-------|-----|
| `/domain-design` | Strategic design before scaffolding: bounded context, aggregates, integration pattern, event-model slices, glossary delta |
| `/new-module`, `/new-slice` | Scaffold a bounded context or one feature slice |
| `/exceptions` | Add a domain exception with its HTTP status |
| `/preflight` | Run the gates from `.factory/recipe.yml` in order and stop at the first failure |
| `/openspec-explore`, `/openspec-propose`, `/openspec-apply-change`, `/openspec-sync-specs`, `/openspec-archive-change` | The OpenSpec change workflow |

## Workflow

Run `/domain-design` before `/new-module` or `/new-slice` when a change adds a context, an aggregate, a cross-context interaction, or moves a capability. A single slice inside an existing context with no new terms goes straight to `/new-slice`.

Never run `bin/console make:*` (MakerBundle is not installed). Scaffold with `/new-slice` and `/new-module`, which produce the layout the rules and architecture tests expect.

The gates are the checks listed in `.factory/recipe.yml`, and they run in Docker. Run `/preflight` before pushing or opening a PR. Do not copy the commands into other files; the recipe is the single source.

The coding style comes from PHP CS Fixer (`@Symfony`, then `@PER-CS3x0`, imports sorted alphabetically). Run `composer cs` to see what it would change and `vendor/bin/php-cs-fixer fix` to apply it.

Branches start from `main` and PRs target `main`.

After the gates pass, update whatever the change made stale: the matching file in `docs/`, a rule in `.ai/rules/`, a skill, and the brain vault note. A durable trap or settled decision goes into a rule or the brain vault, not only the PR description. A change that settles an architectural question gets an ADR (`docs/decisions/README.md`).

## Writing

No em dashes. Short, direct sentences; name the file, the command, the number. No filler transitions, no hedging.
