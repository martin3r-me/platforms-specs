<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\{SpecsAcceptanceCriterion, SpecsRequirement};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListAcceptanceCriteriaTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.acceptance-criteria.GET'; }
    public function getDescription(): string { return 'GET /specs/acceptance-criteria - Listet Acceptance Criteria eines Requirements. ERFORDERLICH: requirement_id.'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'requirement_id' => ['type' => 'integer', 'description' => 'ID des Requirements (ERFORDERLICH).']], 'required' => ['requirement_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $reqId = (int)($arguments['requirement_id'] ?? 0); if ($reqId <= 0) return ToolResult::error('VALIDATION_ERROR', 'requirement_id ist erforderlich.');
            $req = SpecsRequirement::with('section.document')->find($reqId);
            if (!$req || $req->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Requirement nicht gefunden (oder kein Zugriff).');
            $criteria = $req->acceptanceCriteria()->orderBy('position')->get();
            $data = $criteria->map(fn ($ac) => ['id' => $ac->id, 'uuid' => $ac->uuid, 'content' => $ac->content, 'position' => $ac->position])->values()->toArray();
            return ToolResult::success(['requirement_id' => $reqId, 'requirement_label' => $req->requirement_id, 'acceptance_criteria' => $data, 'total' => count($data)]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'acceptance-criteria', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
