<?php

namespace Platform\Specs\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Specs\Models\SpecsDocument;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;
use Platform\Organization\Models\OrganizationEntity;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllDocuments = false;

    public function mount()
    {
        $this->showAllDocuments = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
    }

    public function toggleShowAllDocuments()
    {
        $this->showAllDocuments = !$this->showAllDocuments;
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('specs::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedDocuments' => collect(),
                'hasMoreDocuments' => false,
            ]);
        }

        // 1. Dokumente laden
        $myDocuments = SpecsDocument::query()
            ->where('team_id', $teamId)
            ->where('created_by_user_id', $user->id)
            ->orderBy('name')
            ->get();

        $allDocuments = SpecsDocument::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        $documentsToShow = $this->showAllDocuments
            ? $allDocuments
            : $myDocuments;

        $hasMoreDocuments = $allDocuments->count() > $myDocuments->count();

        // 2. Entity-Verknüpfungen laden aus beiden Quellen
        $documentIds = $documentsToShow->pluck('id')->toArray();

        $entityDocumentMap = [];
        $linkedDocumentIds = [];

        $contextMorphTypes = ['specs_document', SpecsDocument::class];

        // a) OrganizationContext
        $contexts = OrganizationContext::query()
            ->whereIn('contextable_type', $contextMorphTypes)
            ->whereIn('contextable_id', $documentIds)
            ->where('is_active', true)
            ->with(['organizationEntity.type'])
            ->get();

        foreach ($contexts as $ctx) {
            $entityId = $ctx->organization_entity_id;
            $documentId = $ctx->contextable_id;
            if ($entityId) {
                $entityDocumentMap[$entityId][] = $documentId;
                $linkedDocumentIds[] = $documentId;
            }
        }

        // b) OrganizationEntityLink
        $entityLinks = OrganizationEntityLink::query()
            ->whereIn('linkable_type', $contextMorphTypes)
            ->whereIn('linkable_id', $documentIds)
            ->with(['entity.type'])
            ->get();

        foreach ($entityLinks as $link) {
            $entityId = $link->entity_id;
            $documentId = $link->linkable_id;
            $entityDocumentMap[$entityId][] = $documentId;
            $linkedDocumentIds[] = $documentId;
        }

        // Deduplizieren
        foreach ($entityDocumentMap as $entityId => $dids) {
            $entityDocumentMap[$entityId] = array_unique($dids);
        }
        $linkedDocumentIds = array_unique($linkedDocumentIds);

        // 3. Gruppieren: EntityType → Entity → Documents
        $entityTypeGroups = collect();

        $entityIds = array_keys($entityDocumentMap);
        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            $groupedByType = [];
            foreach ($entityDocumentMap as $entityId => $documentIdsList) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }
                $typeId = $entity->type->id;
                if (!isset($groupedByType[$typeId])) {
                    $groupedByType[$typeId] = [
                        'type_id' => $typeId,
                        'type_name' => $entity->type->name,
                        'type_icon' => $entity->type->icon,
                        'sort_order' => $entity->type->sort_order ?? 999,
                        'entities' => [],
                    ];
                }
                if (!isset($groupedByType[$typeId]['entities'][$entityId])) {
                    $groupedByType[$typeId]['entities'][$entityId] = [
                        'entity_id' => $entityId,
                        'entity_name' => $entity->name,
                        'documents' => collect(),
                    ];
                }
                foreach ($documentIdsList as $did) {
                    $document = $documentsToShow->firstWhere('id', $did);
                    if ($document) {
                        $groupedByType[$typeId]['entities'][$entityId]['documents']->push($document);
                    }
                }
            }

            $entityTypeGroups = collect($groupedByType)
                ->sortBy('sort_order')
                ->map(function ($group) {
                    $group['entities'] = collect($group['entities'])
                        ->sortBy('entity_name')
                        ->values();
                    return $group;
                })
                ->values();
        }

        // 4. Unverknüpfte Dokumente
        $unlinkedDocuments = $documentsToShow->filter(function ($doc) use ($linkedDocumentIds) {
            return !in_array($doc->id, $linkedDocumentIds);
        })->values();

        return view('specs::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedDocuments' => $unlinkedDocuments,
            'hasMoreDocuments' => $hasMoreDocuments,
        ]);
    }
}
