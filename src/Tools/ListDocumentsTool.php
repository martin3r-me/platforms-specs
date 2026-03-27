<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListDocumentsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.documents.GET'; }

    public function getDescription(): string
    {
        return 'GET /specs/documents - Listet Specs-Dokumente (Lasten-/Pflichtenhefte). Parameter: team_id (optional), document_type (optional: lastenheft/pflichtenheft), status (optional), filters/search/sort/limit/offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.'],
                    'status' => ['type' => 'string', 'enum' => SpecsDocument::STATUSES, 'description' => 'Optional: Filter nach Status.'],
                    'document_type' => ['type' => 'string', 'enum' => SpecsDocument::DOCUMENT_TYPES, 'description' => 'Optional: Filter nach Dokumenttyp (lastenheft/pflichtenheft).'],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $query = SpecsDocument::query()
                ->withCount('sections', 'snapshots', 'comments')
                ->forTeam($teamId);

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }
            if (isset($arguments['document_type'])) {
                $query->byType($arguments['document_type']);
            }

            $this->applyStandardFilters($query, $arguments, ['name', 'status', 'document_type', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, ['name', 'status', 'document_type', 'created_at', 'updated_at'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (SpecsDocument $doc) {
                return [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'name' => $doc->name,
                    'description' => $doc->description,
                    'document_type' => $doc->document_type,
                    'document_type_label' => SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type,
                    'status' => $doc->status,
                    'status_label' => SpecsDocument::STATUS_LABELS[$doc->status] ?? $doc->status,
                    'prefix' => $doc->prefix,
                    'is_public' => $doc->is_public,
                    'linked_document_id' => $doc->linked_document_id,
                    'sections_count' => $doc->sections_count,
                    'snapshots_count' => $doc->snapshots_count,
                    'comments_count' => $doc->comments_count,
                    'team_id' => $doc->team_id,
                    'created_at' => $doc->created_at?->toISOString(),
                    'updated_at' => $doc->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Dokumente: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read', 'tags' => ['specs', 'documents', 'list'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
