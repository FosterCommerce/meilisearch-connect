# Control Panel utility

Open **Utilities -> Meilisearch Connect** to view configured indexes.

For each index, the utility shows its handle, Meilisearch index ID, and type. Managed indexes also show their document count, or the API error if the index is missing from Meilisearch.

Managed indexes have these actions:

| Action             | What it does                                                       |
|--------------------|--------------------------------------------------------------------|
| Sync Settings      | Creates the index if needed and sends configured settings.         |
| Sync Index         | Queues a sync for the current sources.                             |
| Refresh Index      | Builds a replacement index and swaps it into place.                |
| Flush Index        | Deletes every document and tracking record.                        |
| Clean Up Swap Data | Deletes the temporary indexes that refreshes create, including one a running refresh is using. |

The top-level actions run the same operation for every managed index.

Search-only indexes are shown but have no sync actions.

The sync actions require an admin user.
