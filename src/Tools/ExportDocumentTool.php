<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Services\DocumentService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ExportDocumentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.export.GET'; }
    public function getDescription(): string { return 'GET /specs/export - Exportiert ein Dokument mit allen Sections, Requirements und Summary-Statistiken. ERFORDERLICH: document_id.'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).']], 'required' => ['document_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $docId = (int)($arguments['document_id'] ?? 0); if ($docId <= 0) return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            $docService = new DocumentService();
            return ToolResult::success($docService->exportDocument($doc));
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'export'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
