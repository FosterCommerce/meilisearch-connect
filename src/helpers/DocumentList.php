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
	 * @param string|int $sourceId} The ID of the Entry this document list was generated from.
	 */
	public function __construct(
		array $documentOrDocuments,
		public string|int $sourceId
	) {
		$this->documents = array_is_list($documentOrDocuments) ? $documentOrDocuments : [$documentOrDocuments];
	}
}
