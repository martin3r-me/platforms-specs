<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Services\DocumentService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateSnapshotTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.snapshots.POST'; }
    public function getDescription(): string { return 'POST /specs/snapshots - Erstellt einen Snapshot (Versionsstand) eines Dokuments. ERFORDERLICH: document_id.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).']], 'required' => ['document_id']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            if (!$context->user) return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $docId = (int)($arguments['document_id'] ?? 0); if ($docId <= 0) return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            $docService = new DocumentService();
            $snapshot = $docService->createSnapshot($doc, $context->user->id);
            return ToolResult::success(['id' => $snapshot->id, 'uuid' => $snapshot->uuid, 'version' => $snapshot->version, 'document_id' => $doc->id, 'message' => "Snapshot v{$snapshot->version} erstellt."]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'snapshots', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false]; }
}
