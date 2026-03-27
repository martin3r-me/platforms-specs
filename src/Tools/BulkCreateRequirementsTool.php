<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsSection;
use Platform\Specs\Services\RequirementService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class BulkCreateRequirementsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.bulk.POST'; }
    public function getDescription(): string { return 'POST /specs/requirements/bulk - Erstellt mehrere Requirements auf einmal. ERFORDERLICH: section_id, requirements (Array mit title).'; }
    public function getSchema(): array {
        return $this->mergeWriteSchema(['properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'section_id' => ['type' => 'integer', 'description' => 'ID der Section (ERFORDERLICH).'],
            'requirements' => ['type' => 'array', 'description' => 'Array von Requirements. Jedes Element: {title (required), content, requirement_type, priority, status, metadata}.', 'items' => ['type' => 'object']],
        ], 'required' => ['section_id', 'requirements']]);
    }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            if (!$context->user) return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $sectionId = (int)($arguments['section_id'] ?? 0);
            if ($sectionId <= 0) return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');

            $section = SpecsSection::with('document')->find($sectionId);
            if (!$section || $section->document?->team_id !== $teamId) {
                return ToolResult::error('NOT_FOUND', 'Section nicht gefunden (oder kein Zugriff).');
            }

            $reqsData = $arguments['requirements'] ?? [];
            if (empty($reqsData)) return ToolResult::error('VALIDATION_ERROR', 'requirements darf nicht leer sein.');

            $reqService = new RequirementService();
            $created = $reqService->bulkCreateRequirements($section, $reqsData, $context->user->id);

            $result = array_map(fn ($r) => [
                'id' => $r->id, 'requirement_id' => $r->requirement_id, 'title' => $r->title,
                'requirement_type' => $r->requirement_type, 'priority' => $r->priority,
            ], $created);

            return ToolResult::success([
                'created_count' => count($created), 'requirements' => $result,
                'section_id' => $section->id, 'document_id' => $section->document_id,
                'message' => count($created) . ' Requirements erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'requirements', 'bulk', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
