# {{PRODUCT_NAME}} agent guide

{{PRODUCT_PITCH}}

Symfony application built as vertical slices: `{{PRODUCT_NAMESPACE}}/<Module>/` holds one bounded context per module, `Framework/` holds shared infrastructure. See `docs/ARCHITECTURE.md`.

- Rules: `.ai/rules/`, path-scoped, indexed in `.ai/rules/index.md`. Read the matching rule before editing a file.
- Product brain: decisions, glossary, brief and event model live in the `{{PRODUCT_SLUG}}-docs` brain vault. Read it before planning.
- Skills: `.claude/skills/`. Use `/new-slice` and `/new-module` to scaffold, never `bin/console make:*`. Use `/domain-design` before adding a context or aggregate.
- Gates: listed in `.factory/recipe.yml`, run in Docker. `/preflight` runs them in order and stops at the first failure.
- Never use em dashes in code comments, docs or commit messages.

The full guide is `CLAUDE.md`.
