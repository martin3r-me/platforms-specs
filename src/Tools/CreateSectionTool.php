<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Services\SectionService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.sections.POST'; }

    public function getDescription(): string
    {
        return 'POST /specs/sections - Erstellt eine neue Section in einem Dokument. ERFORDERLICH: document_id, title. Optional: description, parent_id, position.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Titel der Section (ERFORDERLICH).'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'parent_id' => ['type' => 'integer', 'description' => 'Optional: Parent-Section-ID fuer Verschachtelung.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Position.'],
            ],
            'required' => ['document_id', 'title'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $docId = (int)($arguments['document_id'] ?? 0);
            if ($docId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            }

            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) {
                return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            }

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $sectionService = new SectionService();
            $section = $sectionService->createSection($doc, [
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'parent_id' => $arguments['parent_id'] ?? null,
                'position' => $arguments['position'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'position' => $section->position,
                'parent_id' => $section->parent_id,
                'document_id' => $doc->id,
                'message' => 'Section erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Section: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'sections', 'create'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
