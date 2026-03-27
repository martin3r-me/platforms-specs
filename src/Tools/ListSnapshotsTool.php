<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\{SpecsDocument, SpecsDocumentSnapshot};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListSnapshotsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.snapshots.GET'; }
    public function getDescription(): string { return 'GET /specs/snapshots - Listet Snapshots eines Dokuments. ERFORDERLICH: document_id.'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).']], 'required' => ['document_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $docId = (int)($arguments['document_id'] ?? 0); if ($docId <= 0) return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            $snapshots = $doc->snapshots()->get();
            $data = $snapshots->map(fn ($s) => ['id' => $s->id, 'uuid' => $s->uuid, 'version' => $s->version, 'created_at' => $s->created_at?->toISOString()])->values()->toArray();
            return ToolResult::success(['document_id' => $docId, 'snapshots' => $data, 'total' => count($data)]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'snapshots', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
