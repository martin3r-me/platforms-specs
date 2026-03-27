<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsTrace;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class DeleteTraceTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.traces.DELETE'; }
    public function getDescription(): string { return 'DELETE /specs/traces - Loescht eine Trace-Verknuepfung. ERFORDERLICH: trace_id.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'trace_id' => ['type' => 'integer', 'description' => 'ID der Trace (ERFORDERLICH).']], 'required' => ['trace_id']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $traceId = (int)($arguments['trace_id'] ?? 0); if ($traceId <= 0) return ToolResult::error('VALIDATION_ERROR', 'trace_id ist erforderlich.');
            $trace = SpecsTrace::with(['sourceRequirement.section.document'])->find($traceId);
            if (!$trace || $trace->sourceRequirement?->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Trace nicht gefunden (oder kein Zugriff).');
            $trace->delete();
            return ToolResult::success(['deleted_id' => $traceId, 'message' => 'Trace geloescht.']);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'traces', 'delete'], 'risk_level' => 'destructive', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false]; }
}
