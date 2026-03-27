<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class DeleteDocumentTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.documents.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /specs/documents - Loescht ein Specs-Dokument (Soft Delete). ERFORDERLICH: document_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).'],
            ],
            'required' => ['document_id'],
        ]);
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

            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) {
                return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            }

            $name = $doc->name;
            $doc->delete();

            return ToolResult::success([
                'deleted_id' => $docId,
                'message' => "Dokument '{$name}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen des Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'documents', 'delete'],
            'risk_level' => 'destructive', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
