<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\{SpecsRequirement, SpecsSection};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class UpdateRequirementTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations, ResolvesSpecsTeam;
    public function getName(): string { return 'specs.requirements.PUT'; }
    public function getDescription(): string { return 'PUT /specs/requirements - Aktualisiert ein Requirement. ERFORDERLICH: requirement_id. Optional: title, content, requirement_type, priority, status, metadata, section_id (zum Verschieben).'; }
    public function getSchema(): array {
        return $this->mergeWriteSchema(['properties' => [
            'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            'requirement_id' => ['type' => 'integer', 'description' => 'ID des Requirements (ERFORDERLICH). Achtung: Dies ist die Datenbank-ID, nicht die Anforderungs-ID (LH-001 etc.).'],
            'title' => ['type' => 'string', 'description' => 'Optional: Neuer Titel.'],
            'content' => ['type' => 'string', 'description' => 'Optional: Neuer Inhalt.'],
            'requirement_type' => ['type' => 'string', 'enum' => SpecsRequirement::REQUIREMENT_TYPES, 'description' => 'Optional: Neuer Typ.'],
            'priority' => ['type' => 'string', 'enum' => SpecsRequirement::PRIORITIES, 'description' => 'Optional: Neue Prioritaet.'],
            'status' => ['type' => 'string', 'enum' => SpecsRequirement::STATUSES, 'description' => 'Optional: Neuer Status.'],
            'metadata' => ['type' => 'object', 'description' => 'Optional: Neue Metadaten.'],
            'section_id' => ['type' => 'integer', 'description' => 'Optional: Requirement in andere Section verschieben.'],
            'position' => ['type' => 'integer', 'description' => 'Optional: Neue Position.'],
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

            $updateData = [];
            if (isset($arguments['title'])) $updateData['title'] = trim($arguments['title']);
            if (array_key_exists('content', $arguments)) $updateData['content'] = $arguments['content'];
            if (isset($arguments['requirement_type'])) $updateData['requirement_type'] = $arguments['requirement_type'];
            if (isset($arguments['priority'])) $updateData['priority'] = $arguments['priority'];
            if (isset($arguments['status'])) $updateData['status'] = $arguments['status'];
            if (array_key_exists('metadata', $arguments)) $updateData['metadata'] = $arguments['metadata'];
            if (isset($arguments['section_id'])) $updateData['section_id'] = (int)$arguments['section_id'];
            if (isset($arguments['position'])) $updateData['position'] = (int)$arguments['position'];

            if (!empty($updateData)) $req->update($updateData);
            $req->refresh();

            return ToolResult::success([
                'id' => $req->id, 'uuid' => $req->uuid, 'requirement_id' => $req->requirement_id,
                'title' => $req->title, 'status' => $req->status, 'priority' => $req->priority,
                'message' => "Requirement {$req->requirement_id} aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Requirements: ' . $e->getMessage());
        }
    }
    public function getMetadata(): array {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['specs', 'requirements', 'update'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
