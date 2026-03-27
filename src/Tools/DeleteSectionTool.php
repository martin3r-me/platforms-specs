<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsSection;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class DeleteSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.sections.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /specs/sections - Loescht eine Section (und alle Requirements darin). ERFORDERLICH: section_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'section_id' => ['type' => 'integer', 'description' => 'ID der Section (ERFORDERLICH).'],
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

            $title = $section->title;
            $section->delete();

            return ToolResult::success([
                'deleted_id' => $sectionId,
                'message' => "Section '{$title}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen der Section: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'sections', 'delete'],
            'risk_level' => 'destructive', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
