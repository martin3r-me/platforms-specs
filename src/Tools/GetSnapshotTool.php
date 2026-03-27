<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\SpecsDocumentSnapshot;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class GetSnapshotTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.snapshot.GET'; }
    public function getDescription(): string { return 'GET /specs/snapshot - Zeigt einen einzelnen Snapshot mit allen Daten. ERFORDERLICH: snapshot_id.'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'snapshot_id' => ['type' => 'integer', 'description' => 'ID des Snapshots (ERFORDERLICH).']], 'required' => ['snapshot_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $snapId = (int)($arguments['snapshot_id'] ?? 0); if ($snapId <= 0) return ToolResult::error('VALIDATION_ERROR', 'snapshot_id ist erforderlich.');
            $snapshot = SpecsDocumentSnapshot::with('document')->find($snapId);
            if (!$snapshot || $snapshot->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Snapshot nicht gefunden (oder kein Zugriff).');
            return ToolResult::success(['id' => $snapshot->id, 'uuid' => $snapshot->uuid, 'version' => $snapshot->version, 'document_id' => $snapshot->document_id, 'created_at' => $snapshot->created_at?->toISOString(), 'snapshot_data' => $snapshot->snapshot_data]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'snapshot', 'get'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
