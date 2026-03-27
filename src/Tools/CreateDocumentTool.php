<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Services\DocumentService;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class CreateDocumentTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.documents.POST'; }

    public function getDescription(): string
    {
        return 'POST /specs/documents - Erstellt ein neues Specs-Dokument (Lastenheft oder Pflichtenheft). ERFORDERLICH: name, document_type (lastenheft/pflichtenheft). Optional: description, status, linked_document_id, prefix.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'name' => ['type' => 'string', 'description' => 'Name des Dokuments (ERFORDERLICH).'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'document_type' => ['type' => 'string', 'enum' => SpecsDocument::DOCUMENT_TYPES, 'description' => 'Dokumenttyp: lastenheft oder pflichtenheft (ERFORDERLICH).'],
                'status' => ['type' => 'string', 'enum' => SpecsDocument::STATUSES, 'description' => 'Optional: Status. Default: backlog.'],
                'linked_document_id' => ['type' => 'integer', 'description' => 'Optional: ID eines verknuepften Dokuments (z.B. Lastenheft zu Pflichtenheft).'],
                'prefix' => ['type' => 'string', 'description' => 'Optional: Prefix fuer Requirement-IDs. Default: LH oder PH je nach Typ.'],
            ],
            'required' => ['name', 'document_type'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $documentType = $arguments['document_type'] ?? '';
            if (!in_array($documentType, SpecsDocument::DOCUMENT_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'document_type muss lastenheft oder pflichtenheft sein.');
            }

            $documentService = new DocumentService();
            $doc = $documentService->createDocument([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'document_type' => $documentType,
                'status' => $arguments['status'] ?? SpecsDocument::STATUS_BACKLOG,
                'linked_document_id' => $arguments['linked_document_id'] ?? null,
                'prefix' => $arguments['prefix'] ?? null,
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $doc->id,
                'uuid' => $doc->uuid,
                'name' => $doc->name,
                'document_type' => $doc->document_type,
                'status' => $doc->status,
                'prefix' => $doc->prefix,
                'team_id' => $doc->team_id,
                'message' => 'Dokument erstellt (' . (SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type) . ').',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'documents', 'create'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
