# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file. Each rule file is a copy of a Kanon reference with a `paths:` header added, kept in line with the Kanon source repository by the template repository's `scripts/sync-rules.py`. Edit a rule here only to record a decision the product made on purpose.

`symfony.md` is an early overlay with a short rule set. It grows as the Kanon Symfony overlay does, and the template resyncs it with the script then.

| Applies to | Rule file | Governs |
| --- | --- | --- |
| {{PRODUCT_NAMESPACE}}/**, Framework/**, tools/** | .ai/rules/php-core.md | Every production PHP class, whatever the framework |
| {{PRODUCT_NAMESPACE}}/** | .ai/rules/reliability.md | Batch imports, bulk jobs and other code that processes many independent records |
| {{PRODUCT_NAMESPACE}}/**, Framework/**, config/** | .ai/rules/symfony.md | Symfony controllers, services, Messenger message handlers, console commands |
