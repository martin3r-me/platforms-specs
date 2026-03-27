<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsSection;
use Platform\Specs\Services\RequirementService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ReorderRequirementsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.reorder.PUT'; }
    public function getDescription(): string { return 'PUT /specs/requirements/reorder - Sortiert Requirements einer Section neu. ERFORDERLICH: section_id, requirement_ids (Array in neuer Reihenfolge).'; }
    public function getSchema(): array {
        return $this->mergeWriteSchema(['properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'section_id' => ['type' => 'integer', 'description' => 'ID der Section (ERFORDERLICH).'],
            'requirement_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'IDs der Requirements in neuer Reihenfolge (ERFORDERLICH).'],
        ], 'required' => ['section_id', 'requirement_ids']]);
    }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $sectionId = (int)($arguments['section_id'] ?? 0);
            $section = SpecsSection::with('document')->find($sectionId);
            if (!$section || $section->document?->team_id !== $teamId) {
                return ToolResult::error('NOT_FOUND', 'Section nicht gefunden (oder kein Zugriff).');
            }

            $ids = $arguments['requirement_ids'] ?? [];
            if (empty($ids)) return ToolResult::error('VALIDATION_ERROR', 'requirement_ids darf nicht leer sein.');

            $reqService = new RequirementService();
            $reqService->reorderRequirements($section, $ids);

            return ToolResult::success(['message' => count($ids) . ' Requirements neu sortiert.', 'section_id' => $sectionId]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Sortieren: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'requirements', 'reorder'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
