<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\SpecsDocumentSnapshot;
use Platform\Specs\Services\DocumentService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CompareSnapshotsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.snapshots.compare.GET'; }
    public function getDescription(): string { return 'GET /specs/snapshots/compare - Vergleicht zwei Snapshots. ERFORDERLICH: snapshot_a_id, snapshot_b_id.'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'snapshot_a_id' => ['type' => 'integer', 'description' => 'ID des ersten Snapshots (ERFORDERLICH).'], 'snapshot_b_id' => ['type' => 'integer', 'description' => 'ID des zweiten Snapshots (ERFORDERLICH).']], 'required' => ['snapshot_a_id', 'snapshot_b_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $aId = (int)($arguments['snapshot_a_id'] ?? 0); $bId = (int)($arguments['snapshot_b_id'] ?? 0);
            if ($aId <= 0 || $bId <= 0) return ToolResult::error('VALIDATION_ERROR', 'snapshot_a_id und snapshot_b_id sind erforderlich.');
            $a = SpecsDocumentSnapshot::with('document')->find($aId);
            $b = SpecsDocumentSnapshot::with('document')->find($bId);
            if (!$a || $a->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Snapshot A nicht gefunden.');
            if (!$b || $b->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Snapshot B nicht gefunden.');
            $docService = new DocumentService();
            return ToolResult::success($docService->compareSnapshots($a, $b));
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'snapshots', 'compare'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
