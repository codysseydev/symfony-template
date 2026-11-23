---
name: domain-design
description: >-
  Strategic-design procedure that runs before any scaffolding skill whenever a
  change adds a bounded context, an aggregate, a cross-module interaction, or
  moves a capability between modules. Trigger on: planning a new capability, a new
  module, "where does this belong", context map, domain model, aggregate,
  invariant, ubiquitous language, event model, and on reviewing an existing plan
  or OpenSpec proposal for placement. Not for a single slice inside an existing
  module that introduces no new terms (go straight to new-slice), for scaffolding
  itself, or for bug review.
---

# Domain Design

The design procedure that runs before `/new-module`, `/new-slice` and
`/openspec-propose`. Each step writes a named section of the OpenSpec design
document (or a scratch document before a change exists). The last step is the only
one that touches the brain vault and `docs/ARCHITECTURE.md`.

The product's glossary, context map, decisions and event model live in the
`{{PRODUCT_SLUG}}-docs` brain vault. The decision log is its Key Decisions Log.

## Trigger and skip

Run it when the change adds a bounded context, an aggregate, a cross-module
interaction, or moves a capability from one module to another. Skip it for a slice
that lives inside one existing module and introduces no new vocabulary.

## The steps

1. **Read the model.** Read the brain vault's brief, glossary, event model and Key
   Decisions Log, `docs/ARCHITECTURE.md`, the module list, and the OpenSpec specs
   for any capability the change touches. No artifact; every later step reads this.

2. **Vocabulary pass.** List every noun and verb the request uses and map each to
   a glossary term. A word that appears in two modules with different meanings is a
   boundary signal, not a naming problem: record it as a decision and keep both
   meanings. Artifact: the "Ubiquitous language delta" section, a table of new or
   changed terms, or "no new terms."

3. **Classify the subdomain.** Core, supporting or generic, one sentence each.
   Generic work pushes toward an off-the-shelf package behind an adapter class
   (`.ai/rules/`). Feeds the placement section.

4. **Place it.** List candidate owning modules and check each against
   `.ai/rules/index.md`: a read-only context takes no writes, a slice that
   needs another module's classes is misplaced. Name who the actor is and whether
   the words collide with an existing module. Artifact: "Bounded context
   placement," with the owning module and the rule that decided it.

5. **Aggregates and invariants.** For each state change name the aggregate root (a
   Doctrine entity), the invariants it protects and the entity methods that hold them
   (`.ai/rules/`), the status enum and its transitions, and what is
   copied into another module instead of shared. Artifact: "Aggregates and
   invariants" under the event-model slices.

6. **Event-model slices.** One card per slice: State Change (command, aggregate,
   event), State View (query, read model), Automation (event, message handler or
   schedule, command), Translation (external event, adapter, domain event). Each card names
   its actor, its Given/When/Then rules and the class names. This list is the
   direct input to `/new-slice` and `/new-module`. Artifact: the "Event-model
   slices" section; mirror it into the brain vault's event model.

7. **Map the contexts.** For every arrow that crosses a module boundary, pick one
   pattern: published event or contract, customer-supplier, anti-corruption layer
   around a vendor, separate ways, or materialize (copy the data you need into your
   own table from an event). Name the architecture test or the `.ai/rules/`
   clause that enforces it. Artifact: "Integration patterns."

8. **Design it twice.** Default on for a new module or a moved capability. Launch
   two `Plan` subagents in parallel, each proposing its own placement from the
   artifacts of steps 1 to 7, then compare on actor, word collisions, coupling and
   duplicated state machines. Recommend one. Artifact: "Alternatives considered."

9. **ADR test.** Did "why not the obvious alternative" need explaining in steps 4,
   7 or 8? If yes, record the decision in the brain vault's Key Decisions Log, or
   in `docs/decisions/` when it concerns only this codebase. If no, write "no ADR:
   the placement follows existing rules."

10. **Hand off.** Write or update the OpenSpec proposal and design with the
    sections from step 2 onward: Bounded context placement, Integration patterns,
    Ubiquitous language delta, Event-model slices, Enforcement, ADR test, plus delta
    specs (one capability per module touched) and tasks. Apply the glossary and
    module-list deltas in the same PR as the code. A PR that changes which module
    owns a capability or adds a cross-module arrow without updating them is
    incomplete.

## Review mode

`/domain-design review <change-name | branch>` runs the same steps against an
existing plan or diff. Ask one question at a time, each with a recommended answer.
When a term is settled mid-review, update the glossary in the brain vault inline.
Offer an ADR only when step 9 passes.
