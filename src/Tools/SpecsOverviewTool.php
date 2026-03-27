<?php

namespace Platform\Specs\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class SpecsOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'specs.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /specs/overview - Zeigt Uebersicht ueber das Specs Modul (Lasten-/Pflichtenhefte, Konzepte, verfuegbare Tools).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'module' => 'specs',
                'description' => 'Modul fuer Lasten- und Pflichtenhefte mit Anforderungsmanagement, Traceability und Abnahme-Workflow.',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'document_types' => [
                    'lastenheft' => 'Beschreibt WAS der Auftraggeber will (Kundenanforderungen). Prefix: LH-xxx.',
                    'pflichtenheft' => 'Beschreibt WIE die Anforderungen umgesetzt werden (technische Spezifikation). Prefix: PH-xxx.',
                ],
                'document_statuses' => [
                    'backlog' => 'Noch nicht begonnen',
                    'in_progress' => 'In Bearbeitung',
                    'review' => 'Zur Pruefung/Abnahme',
                    'validated' => 'Vom Kunden abgenommen',
                    'archived' => 'Archiviert',
                ],
                'requirement_types' => [
                    'functional' => 'Funktionale Anforderung',
                    'non_functional' => 'Nicht-funktionale Anforderung (Performance, Security, Usability...)',
                    'constraint' => 'Rahmenbedingung / Randbedingung',
                    'user_story' => 'User Story (Als [Rolle] moechte ich [X], damit [Y])',
                    'use_case' => 'Use Case (Actor, Precondition, Steps, Postcondition)',
                ],
                'requirement_priorities' => [
                    'must' => 'Muss-Anforderung (MoSCoW)',
                    'should' => 'Soll-Anforderung',
                    'could' => 'Kann-Anforderung',
                    'wont' => 'Wird nicht umgesetzt (diesmal)',
                ],
                'requirement_statuses' => [
                    'draft' => 'Entwurf',
                    'approved' => 'Abgenommen',
                    'implemented' => 'Umgesetzt',
                    'verified' => 'Verifiziert/Getestet',
                ],
                'concepts' => [
                    'specs_documents' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsDocument',
                        'table' => 'specs_documents',
                        'key_fields' => ['id', 'uuid', 'name', 'description', 'document_type', 'status', 'prefix', 'team_id', 'linked_document_id', 'is_public', 'public_token'],
                        'note' => 'Ein Lastenheft oder Pflichtenheft. Hat Sections mit Requirements.',
                    ],
                    'specs_sections' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsSection',
                        'table' => 'specs_sections',
                        'key_fields' => ['id', 'uuid', 'document_id', 'parent_id', 'title', 'description', 'position'],
                        'note' => 'Kapitel/Abschnitt eines Dokuments. Hierarchisch via parent_id.',
                    ],
                    'specs_requirements' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsRequirement',
                        'table' => 'specs_requirements',
                        'key_fields' => ['id', 'uuid', 'section_id', 'requirement_id', 'title', 'content', 'requirement_type', 'priority', 'status', 'position', 'metadata'],
                        'note' => 'Einzelne Anforderung innerhalb einer Section. Auto-ID (LH-001, PH-042).',
                    ],
                    'specs_acceptance_criteria' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsAcceptanceCriterion',
                        'table' => 'specs_acceptance_criteria',
                        'key_fields' => ['id', 'uuid', 'requirement_id', 'content', 'position'],
                        'note' => 'Abnahmekriterien pro Requirement (Given/When/Then oder Freitext).',
                    ],
                    'specs_traces' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsTrace',
                        'table' => 'specs_traces',
                        'key_fields' => ['id', 'uuid', 'source_requirement_id', 'target_requirement_id', 'description'],
                        'note' => 'Verknuepfung LH-Requirement -> PH-Requirement (Traceability-Matrix).',
                    ],
                    'specs_comments' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsComment',
                        'table' => 'specs_comments',
                        'key_fields' => ['id', 'uuid', 'document_id', 'section_id', 'requirement_id', 'parent_id', 'content'],
                        'note' => 'Kommentare auf Dokument/Section/Requirement-Ebene mit Replies.',
                    ],
                    'specs_document_snapshots' => [
                        'model' => 'Platform\\Specs\\Models\\SpecsDocumentSnapshot',
                        'table' => 'specs_document_snapshots',
                        'key_fields' => ['id', 'uuid', 'document_id', 'version', 'snapshot_data'],
                        'note' => 'Versionierte Snapshots eines Dokuments fuer Vergleiche.',
                    ],
                ],
                'related_tools' => [
                    'overview' => 'specs.overview.GET',
                    'documents' => [
                        'list' => 'specs.documents.GET',
                        'get' => 'specs.document.GET',
                        'create' => 'specs.documents.POST',
                        'update' => 'specs.documents.PUT',
                        'delete' => 'specs.documents.DELETE',
                    ],
                    'sections' => [
                        'list' => 'specs.sections.GET',
                        'create' => 'specs.sections.POST',
                        'update' => 'specs.sections.PUT',
                        'delete' => 'specs.sections.DELETE',
                    ],
                    'requirements' => [
                        'list' => 'specs.requirements.GET',
                        'create' => 'specs.requirements.POST',
                        'update' => 'specs.requirements.PUT',
                        'delete' => 'specs.requirements.DELETE',
                        'bulk_create' => 'specs.requirements.bulk.POST',
                    ],
                    'acceptance_criteria' => [
                        'list' => 'specs.acceptance-criteria.GET',
                        'create' => 'specs.acceptance-criteria.POST',
                        'update' => 'specs.acceptance-criteria.PUT',
                        'delete' => 'specs.acceptance-criteria.DELETE',
                    ],
                    'traces' => [
                        'list' => 'specs.traces.GET',
                        'create' => 'specs.traces.POST',
                        'delete' => 'specs.traces.DELETE',
                        'coverage' => 'specs.coverage.GET',
                    ],
                    'snapshots' => [
                        'create' => 'specs.snapshots.POST',
                        'list' => 'specs.snapshots.GET',
                        'get' => 'specs.snapshot.GET',
                        'compare' => 'specs.snapshots.compare.GET',
                    ],
                    'comments' => [
                        'list' => 'specs.comments.GET',
                    ],
                    'utilities' => [
                        'export' => 'specs.export.GET',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Specs-Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'specs'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
