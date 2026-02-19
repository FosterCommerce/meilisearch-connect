# Release Notes for Meilisearch Connect

# Unreleased

- Fix the sync job progress being reset when each index starts synchronzing.
- Restores the auto-sync filter for ElementQuery-based indexes.
- Adds document dependency tracking for better auto-sync.
- Includes the query offset if present when paging through the results of an ElementQuery.

# 1.1.4 - 2025-08-27
- Allow extra options to be passed through to the fetch function

# 1.1.3 - 2025-08-24
- Added support for events to be triggered before and after data is synced to Meilisearch

## 1.1.2 - 2025-07-17
- Added an error message to the indices utility when an index is not found or any other `ApiException` is thrown

## 1.1.1 - 2025-07-16
- Fixed a bug where a query callable wouldn't resolve correctly when an element was saved

## 1.1.0 - 2025-07-09
- Added support for element queries to be passed as callables

## 1.0.2 - 2025-07-07
- Added support for indexing multiple documents per item (thanks [bramnjissen](https://github.com/bramnijssen))
- Fixed a bug where the element ID was being used to delete a document instead of the indexed primary key
- Fixed an issue where it could sometimes be possible to delete an element across multiple indexes when indexes were configured per site and the element was disabled for some sites

## 1.0.1 - 2025-06-12
- Added support for facet distribution (thanks [bramnjissen](https://github.com/bramnijssen))

## 1.0.0 - 2025-02-26
- Initial release
