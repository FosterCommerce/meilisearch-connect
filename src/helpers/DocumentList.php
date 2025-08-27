<?php

namespace fostercommerce\meilisearch\helpers;

/**
 * @phpstan-type Document array<non-empty-string, mixed>
 */
class DocumentList
{
	/**
	 * @phpstan-var Document[]
	 */
	public array $documents;

	/**
	 * @param Document|Document[] $documentOrDocuments A single document or a list of documents
	 * @param string $sourceHandle The handle of the Source this document list was generated from, e.g. the Element ID.
	 * @param string[] $dependentSourceHandles IDs of Elements that this document depends on. When a dependent Element is updated or deleted, the source Element will be synced as well.
	 */
	public function __construct(
		array $documentOrDocuments,
		public string $sourceHandle,
		public array $dependentSourceHandles,
	) {
		$this->documents = array_is_list($documentOrDocuments) ? $documentOrDocuments : [$documentOrDocuments];
	}
}
