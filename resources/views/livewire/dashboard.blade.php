<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'icon' => 'document-text'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        {{-- Status-Uebersicht --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            @foreach(\Platform\Specs\Models\SpecsDocument::STATUSES as $status)
                <x-ui-dashboard-tile
                    :title="\Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$status]"
                    :count="$statusCounts[$status] ?? 0"
                    :icon="str_replace('heroicon-o-', '', \Platform\Specs\Models\SpecsDocument::STATUS_ICONS[$status])"
                    variant="secondary"
                />
            @endforeach
        </div>

        {{-- Typ-Uebersicht --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            @foreach(\Platform\Specs\Models\SpecsDocument::DOCUMENT_TYPES as $type)
                <x-ui-dashboard-tile
                    :title="\Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$type]"
                    :count="$typeCounts[$type] ?? 0"
                    icon="document-text"
                    variant="secondary"
                />
            @endforeach
        </div>

        {{-- Letzte Dokumente --}}
        <x-ui-panel title="Letzte Dokumente" subtitle="Zuletzt bearbeitete Lasten- und Pflichtenhefte">
            @forelse($documents as $doc)
                <a href="{{ route('specs.documents.show', $doc) }}"
                   class="flex items-center gap-3 p-3 rounded-lg hover:bg-[var(--ui-muted-5)] transition">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate">{{ $doc->name }}</div>
                        <div class="text-[var(--ui-muted)] text-sm">
                            {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type }}
                            &middot; {{ $doc->sections_count }} Sections
                            &middot; {{ $doc->comments_count }} Kommentare
                            &middot; {{ $doc->updated_at?->diffForHumans() }}
                        </div>
                    </div>
                    <x-ui-badge :variant="\Platform\Specs\Models\SpecsDocument::STATUS_VARIANTS[$doc->status] ?? 'secondary'">
                        {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$doc->status] ?? $doc->status }}
                    </x-ui-badge>
                </a>
            @empty
                <div class="p-8 text-center text-[var(--ui-muted)]">
                    Noch keine Dokumente vorhanden. Erstelle dein erstes Lastenheft oder Pflichtenheft per AI-Assistent.
                </div>
            @endforelse
        </x-ui-panel>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Statistik --}}
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Statistik</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--ui-muted)]">Gesamt</span>
                            <span class="font-semibold">{{ $totalDocuments }}</span>
                        </div>
                        @foreach(\Platform\Specs\Models\SpecsDocument::DOCUMENT_TYPES as $type)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[var(--ui-muted)]">{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$type] }}</span>
                                <span class="font-semibold">{{ $typeCounts[$type] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitaeten" width="w-80" :defaultOpen="false"
            storeKey="activityOpen" side="right">
            <div class="p-6 text-center text-[var(--ui-muted)] text-sm">
                Keine Aktivitaeten vorhanden.
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
