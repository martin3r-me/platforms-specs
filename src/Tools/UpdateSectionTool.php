<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsSection;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class UpdateSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.sections.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /specs/sections - Aktualisiert eine Section. ERFORDERLICH: section_id. Optional: title, description, parent_id, position.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'section_id' => ['type' => 'integer', 'description' => 'ID der Section (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Neuer Titel.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung.'],
                'parent_id' => ['type' => 'integer', 'description' => 'Optional: Neue Parent-Section-ID.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Neue Position.'],
            ],
            'required' => ['section_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $sectionId = (int)($arguments['section_id'] ?? 0);
            if ($sectionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');
            }

            $section = SpecsSection::with('document')->find($sectionId);
            if (!$section || $section->document?->team_id !== $teamId) {
                return ToolResult::error('NOT_FOUND', 'Section nicht gefunden (oder kein Zugriff).');
            }

            $updateData = [];
            if (isset($arguments['title'])) $updateData['title'] = trim($arguments['title']);
            if (array_key_exists('description', $arguments)) $updateData['description'] = $arguments['description'];
            if (array_key_exists('parent_id', $arguments)) $updateData['parent_id'] = $arguments['parent_id'];
            if (isset($arguments['position'])) $updateData['position'] = (int)$arguments['position'];

            if (!empty($updateData)) {
                $section->update($updateData);
            }

            $section->refresh();

            return ToolResult::success([
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'position' => $section->position,
                'parent_id' => $section->parent_id,
                'message' => 'Section aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Section: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'sections', 'update'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
