<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsSection;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListSectionsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.sections.GET'; }

    public function getDescription(): string
    {
        return 'GET /specs/sections - Listet Sections eines Dokuments (hierarchisch). Parameter: document_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).'],
            ],
            'required' => ['document_id'],
        ];
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

            $sections = SpecsSection::query()
                ->where('document_id', $docId)
                ->withCount('requirements', 'children')
                ->orderBy('position')
                ->get();

            $data = $sections->map(fn (SpecsSection $s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'title' => $s->title,
                'description' => $s->description,
                'position' => $s->position,
                'parent_id' => $s->parent_id,
                'requirements_count' => $s->requirements_count,
                'children_count' => $s->children_count,
            ])->values()->toArray();

            return ToolResult::success([
                'document_id' => $docId,
                'document_name' => $doc->name,
                'sections' => $data,
                'total' => count($data),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Sections: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read', 'tags' => ['specs', 'sections', 'list'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
