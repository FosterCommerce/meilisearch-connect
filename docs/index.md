# Meilisearch Connect documentation

Build Meilisearch indexes from any Craft element query, keep them current as content changes, and rebuild them with zero downtime, on a server you can host yourself to keep search costs down.

## Where to go

**Getting started:** [a walkthrough](./getting-started.md) from install to the first search.

**User guide:**

- [Control Panel utility](./user-guide/control-panel-utility.md), view configured indexes and run sync actions

**Dev guide:**

- [Transformers and dependencies](./dev-guide/transformers-and-dependencies.md), turn elements into documents and keep them current
- [Custom data](./dev-guide/custom-data.md), index data that is not a Craft element
- [Sync events](./dev-guide/sync-events.md), inspect or change a document batch

**Reference:**

- [Configuration](./reference/configuration.md), every setting and index option
- [Console commands](./reference/console-commands.md), sync, flush, and refresh indexes
- [Class reference](./reference/class-reference.md), use the plugin from PHP code

**Recipes:**

- [Entry index](./recipes/entry-index.md), index news entries
- [Multiple documents per element](./recipes/multiple-documents-per-element.md), one document per article and tag pair
- [Split an element into content documents](./recipes/split-element-documents.md), one document per paragraph
- [Related element dependencies](./recipes/related-element-dependencies.md), reindex articles when an author changes
- [Twig search](./recipes/search-twig.md), search with filters, sorting, and pagination
