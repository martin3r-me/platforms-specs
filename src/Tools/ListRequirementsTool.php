<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\{SpecsDocument, SpecsRequirement, SpecsSection};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListRequirementsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.GET'; }
    public function getDescription(): string { return 'GET /specs/requirements - Listet Requirements einer Section oder eines Dokuments. Parameter: document_id oder section_id (mindestens eins required).'; }
    public function getSchema(): array {
        return ['type' => 'object', 'properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'document_id' => ['type' => 'integer', 'description' => 'Optional: Alle Requirements eines Dokuments.'],
            'section_id' => ['type' => 'integer', 'description' => 'Optional: Requirements einer Section.'],
            'requirement_type' => ['type' => 'string', 'enum' => SpecsRequirement::REQUIREMENT_TYPES, 'description' => 'Optional: Filter nach Typ.'],
            'priority' => ['type' => 'string', 'enum' => SpecsRequirement::PRIORITIES, 'description' => 'Optional: Filter nach Prioritaet.'],
            'status' => ['type' => 'string', 'enum' => SpecsRequirement::STATUSES, 'description' => 'Optional: Filter nach Status.'],
        ], 'required' => []];
    }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $query = SpecsRequirement::query()->with('section')->withCount('acceptanceCriteria', 'sourceTraces', 'targetTraces');

            if (isset($arguments['section_id'])) {
                $section = SpecsSection::with('document')->find((int)$arguments['section_id']);
                if (!$section || $section->document?->team_id !== $teamId) {
                    return ToolResult::error('NOT_FOUND', 'Section nicht gefunden (oder kein Zugriff).');
                }
                $query->where('section_id', $section->id);
            } elseif (isset($arguments['document_id'])) {
                $doc = SpecsDocument::query()->where('team_id', $teamId)->find((int)$arguments['document_id']);
                if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
                $sectionIds = $doc->sections()->pluck('id');
                $query->whereIn('section_id', $sectionIds);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'document_id oder section_id ist erforderlich.');
            }

            if (isset($arguments['requirement_type'])) $query->where('requirement_type', $arguments['requirement_type']);
            if (isset($arguments['priority'])) $query->where('priority', $arguments['priority']);
            if (isset($arguments['status'])) $query->where('status', $arguments['status']);

            $requirements = $query->orderBy('position')->get();

            $data = $requirements->map(fn (SpecsRequirement $r) => [
                'id' => $r->id, 'uuid' => $r->uuid, 'requirement_id' => $r->requirement_id,
                'title' => $r->title, 'content' => $r->content,
                'requirement_type' => $r->requirement_type, 'type_label' => SpecsRequirement::TYPE_LABELS[$r->requirement_type] ?? $r->requirement_type,
                'priority' => $r->priority, 'priority_label' => SpecsRequirement::PRIORITY_LABELS[$r->priority] ?? $r->priority,
                'status' => $r->status, 'status_label' => SpecsRequirement::STATUS_LABELS[$r->status] ?? $r->status,
                'position' => $r->position, 'section_id' => $r->section_id, 'section_title' => $r->section?->title,
                'acceptance_criteria_count' => $r->acceptance_criteria_count,
                'source_traces_count' => $r->source_traces_count, 'target_traces_count' => $r->target_traces_count,
                'metadata' => $r->metadata,
            ])->values()->toArray();

            return ToolResult::success(['data' => $data, 'total' => count($data), 'team_id' => $teamId]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Requirements: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'requirements', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
