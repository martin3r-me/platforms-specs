<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\{SpecsRequirement, SpecsTrace};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateTraceTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.traces.POST'; }
    public function getDescription(): string { return 'POST /specs/traces - Erstellt eine Trace-Verknuepfung zwischen zwei Requirements (z.B. LH-Req -> PH-Req). ERFORDERLICH: source_requirement_id, target_requirement_id.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'source_requirement_id' => ['type' => 'integer', 'description' => 'ID des Quell-Requirements (z.B. aus Lastenheft) (ERFORDERLICH).'], 'target_requirement_id' => ['type' => 'integer', 'description' => 'ID des Ziel-Requirements (z.B. aus Pflichtenheft) (ERFORDERLICH).'], 'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung der Verknuepfung.']], 'required' => ['source_requirement_id', 'target_requirement_id']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $srcId = (int)($arguments['source_requirement_id'] ?? 0);
            $tgtId = (int)($arguments['target_requirement_id'] ?? 0);
            if ($srcId <= 0 || $tgtId <= 0) return ToolResult::error('VALIDATION_ERROR', 'source_requirement_id und target_requirement_id sind erforderlich.');
            $src = SpecsRequirement::with('section.document')->find($srcId);
            $tgt = SpecsRequirement::with('section.document')->find($tgtId);
            if (!$src || $src->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Source-Requirement nicht gefunden (oder kein Zugriff).');
            if (!$tgt || $tgt->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Target-Requirement nicht gefunden (oder kein Zugriff).');
            $existing = SpecsTrace::where('source_requirement_id', $srcId)->where('target_requirement_id', $tgtId)->exists();
            if ($existing) return ToolResult::error('VALIDATION_ERROR', 'Diese Trace-Verknuepfung existiert bereits.');
            $trace = SpecsTrace::create(['source_requirement_id' => $srcId, 'target_requirement_id' => $tgtId, 'description' => $arguments['description'] ?? null]);
            return ToolResult::success(['id' => $trace->id, 'uuid' => $trace->uuid, 'source' => $src->requirement_id, 'target' => $tgt->requirement_id, 'message' => "Trace {$src->requirement_id} -> {$tgt->requirement_id} erstellt."]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'traces', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false]; }
}
