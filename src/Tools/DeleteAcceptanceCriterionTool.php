<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsAcceptanceCriterion;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class DeleteAcceptanceCriterionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.acceptance-criteria.DELETE'; }
    public function getDescription(): string { return 'DELETE /specs/acceptance-criteria - Loescht ein Acceptance Criterion. ERFORDERLICH: criterion_id.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'criterion_id' => ['type' => 'integer', 'description' => 'ID des Criterions (ERFORDERLICH).']], 'required' => ['criterion_id']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $acId = (int)($arguments['criterion_id'] ?? 0); if ($acId <= 0) return ToolResult::error('VALIDATION_ERROR', 'criterion_id ist erforderlich.');
            $ac = SpecsAcceptanceCriterion::with('requirement.section.document')->find($acId);
            if (!$ac || $ac->requirement?->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Acceptance Criterion nicht gefunden (oder kein Zugriff).');
            $ac->delete();
            return ToolResult::success(['deleted_id' => $acId, 'message' => 'Acceptance Criterion geloescht.']);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'acceptance-criteria', 'delete'], 'risk_level' => 'destructive', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false]; }
}
