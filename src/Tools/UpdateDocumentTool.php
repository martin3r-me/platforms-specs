<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class UpdateDocumentTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesSpecsTeam;

    public function getName(): string { return 'specs.documents.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /specs/documents - Aktualisiert ein Specs-Dokument. ERFORDERLICH: document_id. Optional: name, description, status, linked_document_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).'],
                'name' => ['type' => 'string', 'description' => 'Optional: Neuer Name.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung.'],
                'status' => ['type' => 'string', 'enum' => SpecsDocument::STATUSES, 'description' => 'Optional: Neuer Status.'],
                'linked_document_id' => ['type' => 'integer', 'description' => 'Optional: Verknuepftes Dokument.'],
                'is_public' => ['type' => 'boolean', 'description' => 'Optional: Public-Link aktivieren/deaktivieren.'],
                'entity_id' => ['type' => 'integer', 'description' => 'Optional: ID einer Organisations-Entity, mit der das Dokument verknuepft werden soll. Nutze "organization.entities.GET" um Entity-IDs zu finden.'],
            ],
            'required' => ['document_id'],
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

            $updateData = [];
            if (isset($arguments['name'])) $updateData['name'] = trim($arguments['name']);
            if (array_key_exists('description', $arguments)) $updateData['description'] = $arguments['description'];
            if (isset($arguments['status'])) $updateData['status'] = $arguments['status'];
            if (isset($arguments['linked_document_id'])) $updateData['linked_document_id'] = $arguments['linked_document_id'];

            if (isset($arguments['is_public'])) {
                if ($arguments['is_public'] && !$doc->public_token) {
                    $doc->generatePublicToken();
                } elseif (!$arguments['is_public']) {
                    $updateData['is_public'] = false;
                }
            }

            if (!empty($updateData)) {
                $doc->update($updateData);
            }

            // Entity-Link erstellen/aktualisieren (falls entity_id angegeben)
            if (!empty($arguments['entity_id'])) {
                $entity = \Platform\Organization\Models\OrganizationEntity::find($arguments['entity_id']);
                if ($entity) {
                    // Alte Links entfernen und neuen erstellen
                    \Platform\Organization\Models\OrganizationEntityLink::where('linkable_type', 'specs_document')
                        ->where('linkable_id', $doc->id)
                        ->delete();

                    \Platform\Organization\Models\OrganizationEntityLink::create([
                        'entity_id' => $entity->id,
                        'linkable_type' => 'specs_document',
                        'linkable_id' => $doc->id,
                        'team_id' => $teamId,
                        'created_by_user_id' => $context->user->id,
                    ]);
                }
            }

            $doc->refresh();

            return ToolResult::success([
                'id' => $doc->id,
                'uuid' => $doc->uuid,
                'name' => $doc->name,
                'status' => $doc->status,
                'is_public' => $doc->is_public,
                'public_url' => $doc->getPublicUrl(),
                'message' => 'Dokument aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['specs', 'documents', 'update'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
