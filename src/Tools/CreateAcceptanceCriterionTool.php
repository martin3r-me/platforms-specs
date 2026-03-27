<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsRequirement;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateAcceptanceCriterionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.acceptance-criteria.POST'; }
    public function getDescription(): string { return 'POST /specs/acceptance-criteria - Erstellt ein Acceptance Criterion. ERFORDERLICH: requirement_id, content.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'requirement_id' => ['type' => 'integer', 'description' => 'ID des Requirements (ERFORDERLICH).'], 'content' => ['type' => 'string', 'description' => 'Inhalt des Kriteriums (ERFORDERLICH). Z.B. Given/When/Then oder Freitext.'], 'position' => ['type' => 'integer', 'description' => 'Optional: Position.']], 'required' => ['requirement_id', 'content']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $reqId = (int)($arguments['requirement_id'] ?? 0); if ($reqId <= 0) return ToolResult::error('VALIDATION_ERROR', 'requirement_id ist erforderlich.');
            $req = SpecsRequirement::with('section.document')->find($reqId);
            if (!$req || $req->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Requirement nicht gefunden (oder kein Zugriff).');
            $content = trim((string)($arguments['content'] ?? '')); if ($content === '') return ToolResult::error('VALIDATION_ERROR', 'content ist erforderlich.');
            $position = $arguments['position'] ?? (($req->acceptanceCriteria()->max('position') ?? 0) + 1);
            $ac = $req->acceptanceCriteria()->create(['content' => $content, 'position' => $position]);
            return ToolResult::success(['id' => $ac->id, 'uuid' => $ac->uuid, 'content' => $ac->content, 'position' => $ac->position, 'requirement_id' => $reqId, 'message' => 'Acceptance Criterion erstellt.']);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'acceptance-criteria', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false]; }
}
