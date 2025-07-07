# Release Notes for Meilisearch Connect

## 1.0.2 - 2025-07-07
- Added support for indexing multiple documents per item (thanks [bramnjissen](https://github.com/bramnijssen))
- Fixed a bug where the element ID was being used to delete a document instead of the indexed primary key
- Fixed an issue where it could sometimes be possible to delete an element across multiple indexes when indexes were configured per site and the element was disabled for some sites

## 1.0.1 - 2025-06-12
- Added support for facet distribution (thanks [bramnjissen](https://github.com/bramnijssen))

## 1.0.0 - 2025-02-26
- Initial release
