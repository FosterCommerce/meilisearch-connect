# Control Panel utility

Open **Utilities -> Meilisearch Connect** to view configured indexes.

For each index, the utility shows its handle, Meilisearch index ID, type, and document count. If an index is missing from Meilisearch, it shows the API error.

Managed indexes have these actions:

| Action             | What it does                                                       |
|--------------------|--------------------------------------------------------------------|
| Sync Settings      | Creates the index if needed and sends configured settings.         |
| Sync Index         | Queues a sync for the current sources.                             |
| Refresh Index      | Builds a replacement index and swaps it into place.                |
| Flush Index        | Deletes every document and tracking record.                        |
| Clean Up Swap Data | Deletes old temporary indexes from failed or incomplete refreshes. |

The top-level actions run the same operation for every managed index.

Search-only indexes are shown but have no sync actions.

The utility requires an admin user.
