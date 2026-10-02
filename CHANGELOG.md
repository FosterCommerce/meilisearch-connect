# Release Notes for Meilisearch Connect

## 2.2.2 - 2026-10-02

### Fixed
- Fixed a bug where saving an element could narrow the element query configured for its index.
- Fixed an error that occurred when an index used an element query that does not extend `ElementQuery`.
- Fixed a bug where static analysis rejected embedder settings that only set `source`.
- Fixed a bug where “Clean Up Swap Data” could leave swap indexes behind.
- Fixed a bug where “Clean Up Swap Data” on an index removed nothing when its index ID differed from its handle.

## 2.2.1 - 2026-07-16

- Paginate through all swap indexes when cleaning up

## 2.2.0 - 2026-07-16

- Attempt to always clean up swap data after any attempt to refresh an index
- Added garbage collection to clean up swap data that can sometimes fail to be cleaned up immediately 

# 2.1.1 - 2026-03-30

- Include the `Install` migration

# 2.1.0 - 2026-03-30

- Improved utility view when no indexes or only search-only indexes are present
- Catch and log possible `ApiExceptions` thrown when using the search variable

# 2.0.0 - 2026-03-18

- Add indexed document tracking
