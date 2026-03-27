<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\{SpecsDocument, SpecsTrace};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListTracesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.traces.GET'; }
    public function getDescription(): string { return 'GET /specs/traces - Listet Traces (Verknuepfungen zwischen LH- und PH-Requirements). Parameter: document_id (required, zeigt alle Traces fuer Requirements dieses Dokuments).'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).']], 'required' => ['document_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $docId = (int)($arguments['document_id'] ?? 0); if ($docId <= 0) return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            $sectionIds = $doc->sections()->pluck('id');
            $reqIds = \Platform\Specs\Models\SpecsRequirement::whereIn('section_id', $sectionIds)->pluck('id');
            $traces = SpecsTrace::with(['sourceRequirement', 'targetRequirement'])
                ->where(fn ($q) => $q->whereIn('source_requirement_id', $reqIds)->orWhereIn('target_requirement_id', $reqIds))
                ->get();
            $data = $traces->map(fn (SpecsTrace $t) => [
                'id' => $t->id, 'uuid' => $t->uuid,
                'source' => ['id' => $t->sourceRequirement?->id, 'requirement_id' => $t->sourceRequirement?->requirement_id, 'title' => $t->sourceRequirement?->title],
                'target' => ['id' => $t->targetRequirement?->id, 'requirement_id' => $t->targetRequirement?->requirement_id, 'title' => $t->targetRequirement?->title],
                'description' => $t->description,
            ])->values()->toArray();
            return ToolResult::success(['document_id' => $docId, 'traces' => $data, 'total' => count($data)]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'traces', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
