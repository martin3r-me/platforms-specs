<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsRequirement;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class DeleteRequirementTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.DELETE'; }
    public function getDescription(): string { return 'DELETE /specs/requirements - Loescht ein Requirement (Soft Delete). ERFORDERLICH: requirement_id.'; }
    public function getSchema(): array {
        return $this->mergeWriteSchema(['properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'requirement_id' => ['type' => 'integer', 'description' => 'ID des Requirements (ERFORDERLICH).'],
        ], 'required' => ['requirement_id']]);
    }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $reqId = (int)($arguments['requirement_id'] ?? 0);
            if ($reqId <= 0) return ToolResult::error('VALIDATION_ERROR', 'requirement_id ist erforderlich.');

            $req = SpecsRequirement::with('section.document')->find($reqId);
            if (!$req || $req->section?->document?->team_id !== $teamId) {
                return ToolResult::error('NOT_FOUND', 'Requirement nicht gefunden (oder kein Zugriff).');
            }

            $reqLabel = $req->requirement_id;
            $req->delete();

            return ToolResult::success(['deleted_id' => $reqId, 'message' => "Requirement {$reqLabel} geloescht."]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen des Requirements: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'requirements', 'delete'], 'risk_level' => 'destructive', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
