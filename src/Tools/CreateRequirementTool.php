<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\{SpecsRequirement, SpecsSection};
use Platform\Specs\Services\RequirementService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateRequirementTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.POST'; }
    public function getDescription(): string { return 'POST /specs/requirements - Erstellt ein neues Requirement. ERFORDERLICH: section_id, title. Optional: content, requirement_type, priority, status, metadata.'; }
    public function getSchema(): array {
        return $this->mergeWriteSchema(['properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'section_id' => ['type' => 'integer', 'description' => 'ID der Section (ERFORDERLICH).'],
            'title' => ['type' => 'string', 'description' => 'Titel (ERFORDERLICH).'],
            'content' => ['type' => 'string', 'description' => 'Optional: Inhalt/Beschreibung.'],
            'requirement_type' => ['type' => 'string', 'enum' => SpecsRequirement::REQUIREMENT_TYPES, 'description' => 'Optional: Typ. Default: functional.'],
            'priority' => ['type' => 'string', 'enum' => SpecsRequirement::PRIORITIES, 'description' => 'Optional: Prioritaet. Default: should.'],
            'status' => ['type' => 'string', 'enum' => SpecsRequirement::STATUSES, 'description' => 'Optional: Status. Default: draft.'],
            'position' => ['type' => 'integer', 'description' => 'Optional: Position.'],
            'metadata' => ['type' => 'object', 'description' => 'Optional: Zusaetzliche Metadaten (JSON). Fuer User Stories: {role, goal, benefit}. Fuer Use Cases: {actor, precondition, steps, postcondition}.'],
        ], 'required' => ['section_id', 'title']]);
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

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');

            $reqService = new RequirementService();
            $req = $reqService->createRequirement($section, [
                'title' => $title,
                'content' => $arguments['content'] ?? null,
                'requirement_type' => $arguments['requirement_type'] ?? 'functional',
                'priority' => $arguments['priority'] ?? 'should',
                'status' => $arguments['status'] ?? 'draft',
                'position' => $arguments['position'] ?? null,
                'metadata' => $arguments['metadata'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $req->id, 'uuid' => $req->uuid, 'requirement_id' => $req->requirement_id,
                'title' => $req->title, 'requirement_type' => $req->requirement_type,
                'priority' => $req->priority, 'status' => $req->status, 'position' => $req->position,
                'section_id' => $section->id, 'document_id' => $section->document_id,
                'message' => "Requirement {$req->requirement_id} erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Requirements: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'requirements', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
