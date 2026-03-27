<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsAcceptanceCriterion;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class UpdateAcceptanceCriterionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.acceptance-criteria.PUT'; }
    public function getDescription(): string { return 'PUT /specs/acceptance-criteria - Aktualisiert ein Acceptance Criterion. ERFORDERLICH: criterion_id. Optional: content, position.'; }
    public function getSchema(): array { return $this->mergeWriteSchema(['properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'criterion_id' => ['type' => 'integer', 'description' => 'ID des Criterions (ERFORDERLICH).'], 'content' => ['type' => 'string', 'description' => 'Optional: Neuer Inhalt.'], 'position' => ['type' => 'integer', 'description' => 'Optional: Neue Position.']], 'required' => ['criterion_id']]); }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $acId = (int)($arguments['criterion_id'] ?? 0); if ($acId <= 0) return ToolResult::error('VALIDATION_ERROR', 'criterion_id ist erforderlich.');
            $ac = SpecsAcceptanceCriterion::with('requirement.section.document')->find($acId);
            if (!$ac || $ac->requirement?->section?->document?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Acceptance Criterion nicht gefunden (oder kein Zugriff).');
            $updateData = [];
            if (isset($arguments['content'])) $updateData['content'] = trim($arguments['content']);
            if (isset($arguments['position'])) $updateData['position'] = (int)$arguments['position'];
            if (!empty($updateData)) $ac->update($updateData);
            $ac->refresh();
            return ToolResult::success(['id' => $ac->id, 'uuid' => $ac->uuid, 'content' => $ac->content, 'position' => $ac->position, 'message' => 'Acceptance Criterion aktualisiert.']);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'acceptance-criteria', 'update'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
