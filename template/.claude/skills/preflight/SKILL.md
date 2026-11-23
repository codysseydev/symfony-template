---
name: preflight
description: >-
  Run the {{PRODUCT_NAME}} pre-PR verification chain: every gate in
  .factory/recipe.yml in order, stopping at the first failure. Use when the user says "preflight", "run the
  checks", "verify before pushing", "is this ready for a PR", or before opening or
  updating a pull request. Not for reviewing someone else's PR.
---

# Preflight verification chain

The gates are the checks in `.factory/recipe.yml`. Read the recipe, then run each
check in the order it lists them, in Docker as the recipe says. Do not copy the
commands from memory; the recipe is the single source and it changes.

## Run order

1. Read `.factory/recipe.yml` and list the checks in order.
2. Run the setup steps once if the stack is not up yet (build, services, install,
   env, migrate).
3. Run the first check. If it fails, stop. Report the failure with its output,
   propose the fix, and continue only once that check passes. A later gate passing
   means nothing while an earlier one fails.
4. Repeat for each check until all pass.

Notes that hold for any recipe:

- The coding-standard check only reports (`--dry-run`). If it fails, run
  `vendor/bin/php-cs-fixer fix` in the container, re-run the check, and mention
  the files it changed so they get committed.
- An architecture check enforces module isolation, class shape, exceptions and
  container access. Never fix a failure by adding to the baseline file; fix the
  code.
- The static analysis check runs at level max with zero errors. Keep it there. Add
  no new ignore annotation without a stated reason. It reads the compiled dev
  container, so `composer phpstan` warms the cache first.
- Rector runs with `--dry-run`. If it reports a change, apply it with
  `vendor/bin/rector process`, then re-run the coding-standard check, because the
  two must agree.
- The test check runs the three suites: Modules, Framework and Architecture. It
  needs MySQL, which the recipe starts.

## Rule audit

Preflight runs the gates only. A rule audit step is added here when the Kanon PR
audit skill ships.

## Final report

One line per gate: PASS or FAIL plus the relevant number (files fixed, violations,
errors, tests passed), then a verdict: ready to push, or what must change first.
