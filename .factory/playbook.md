# Factory project file: symfony-template

Holds symfony-template's specifics for the factory playbook in codysseydev/codyssey-skills (playbooks/factory.md). `factory-onboard` wrote the first version from the repo. A PR that changes a check command or the local setup updates this file in the same change.

## Checks

The gates are the checks in `.factory/recipe.yml`: `unit` (`tests.test_new_repo`, `tests.test_sort_imports`), `drift` (`tests.test_rules_drift`) and `gates` (the Docker gate run with `SYMFONY_TEMPLATE_GATES=1`). The recipe has no other gates.

## Review

Review the diff of `template/`, `module-template/`, `scripts/new-repo.py` and `tests/`. The generated repository must stay green on its own five gates, so a change to `template/` is confirmed by the `gates` check, which builds three repositories and runs each one's recipe. The files in `template/.ai/rules/` (except `index.md`) are copies of the Kanon references in codysseydev/codyssey-skills and are never edited by hand: the `drift` check fails when a copy differs. A rule change goes upstream and comes back through `python3 scripts/sync-rules.py`.

## Scaffolding

`python3 scripts/new-repo.py --name <name> --slug <slug> --pitch <line> --target <dir> [--modules A,B]` builds a product repository from `template/` and `module-template/`. Use it to try a template change by hand. After a change to `template/composer.json`, run `composer update` in a generated repository and copy its `composer.lock` back to `template/`. Work in the checkout directly; there is no implementer agent.

## Local setup

Needs Docker for the `gates` check and network access for the `drift` check, which clones codysseydev/codyssey-skills main into `.cache/` (gitignored). No dependency install: the tests use the Python standard library.

## Cleanup

From the recipe's `teardown` section, in order, from the repo root.

1. `docker`: `for p in aardvark-works session-pack-tracker zebra-desk; do docker compose -p $p-factory down -v --remove-orphans >/dev/null 2>&1; done; true`

## Notes

The `gates` check takes about two minutes and starts three Compose projects named `aardvark-works-factory`, `session-pack-tracker-factory` and `zebra-desk-factory`, with no host ports. The README holds the flag and token reference.

## Post-merge

After the PR merges, the archive run reads the deploy check on the merge commit and looks for new Sentry issues. Name the check run and the Sentry project here, or leave `none`.

- Deploy check: none
- Sentry: none
