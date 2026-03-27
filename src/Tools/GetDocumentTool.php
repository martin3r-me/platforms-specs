<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class GetDocumentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.document.GET'; }

    public function getDescription(): string
    {
        return 'GET /specs/document - Zeigt ein einzelnes Specs-Dokument mit allen Sections und Requirements. Parameter: document_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).'],
            ],
            'required' => ['document_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $docId = (int)($arguments['document_id'] ?? 0);
            if ($docId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            }

            $doc = SpecsDocument::query()
                ->with(['sections.requirements.acceptanceCriteria', 'linkedDocument'])
                ->withCount('snapshots', 'comments')
                ->where('team_id', $teamId)
                ->find($docId);

            if (!$doc) {
                return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            }

            $documentData = $doc->toDocumentArray();

            return ToolResult::success([
                'document' => array_merge($documentData['document'], [
                    'status_label' => SpecsDocument::STATUS_LABELS[$doc->status] ?? $doc->status,
                    'document_type_label' => SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type,
                    'is_public' => $doc->is_public,
                    'public_url' => $doc->getPublicUrl(),
                    'linked_document_name' => $doc->linkedDocument?->name,
                    'snapshots_count' => $doc->snapshots_count,
                    'comments_count' => $doc->comments_count,
                ]),
                'sections' => $documentData['sections'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read', 'tags' => ['specs', 'document', 'get'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
