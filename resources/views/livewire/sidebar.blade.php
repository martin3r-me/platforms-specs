{{-- Modul Header --}}
<div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
    Specs
</div>

{{-- Abschnitt: Allgemein --}}
<x-ui-sidebar-list label="Allgemein">
    <x-ui-sidebar-item :href="route('specs.dashboard')">
        @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
        <span class="ml-2 text-sm">Dashboard</span>
    </x-ui-sidebar-item>
    <x-ui-sidebar-item :href="route('specs.documents.index')">
        @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-secondary)]')
        <span class="ml-2 text-sm">Dokumente</span>
    </x-ui-sidebar-item>
</x-ui-sidebar-list>

{{-- Collapsed: Icons-only --}}
<div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
    <div class="flex flex-col gap-2">
        <a href="{{ route('specs.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-home', 'w-5 h-5')
        </a>
        <a href="{{ route('specs.documents.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-document-text', 'w-5 h-5')
        </a>
    </div>
</div>

{{-- Entity Type Groups (Expandable) --}}
<div>
    <div class="mt-2" x-show="!collapsed">
        @foreach($entityTypeGroups as $typeGroup)
            <x-ui-sidebar-list :label="$typeGroup['type_name']">
                @foreach($typeGroup['entities'] as $entityGroup)
                    <div x-data="{ open: localStorage.getItem('specs.entity.' + {{ $entityGroup['entity_id'] }}) === 'true' }"
                         class="flex flex-col">
                        <button type="button"
                                @click="open = !open; localStorage.setItem('specs.entity.' + {{ $entityGroup['entity_id'] }}, open)"
                                class="flex items-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition w-full text-left">
                            <span class="w-4 h-4 flex-shrink-0 flex items-center justify-center transition-transform"
                                  :class="open ? 'rotate-90' : ''">
                                @svg('heroicon-o-chevron-right', 'w-3 h-3')
                            </span>
                            @svg($typeGroup['type_icon'] ?? 'heroicon-o-rectangle-group', 'w-4 h-4 flex-shrink-0 ml-1 text-[var(--ui-muted)]')
                            <span class="ml-1.5 text-sm font-medium truncate">{{ $entityGroup['entity_name'] }}</span>
                            <span class="ml-auto text-xs text-[var(--ui-muted)]">{{ $entityGroup['documents']->count() }}</span>
                        </button>
                        <div x-show="open" x-collapse class="flex flex-col gap-0.5 pl-4">
                            @foreach($entityGroup['documents'] as $document)
                                <x-ui-sidebar-item :href="route('specs.documents.show', ['document' => $document])" :title="$document->name">
                                    @svg('heroicon-o-document-text', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                                    <div class="flex-1 min-w-0 ml-2 flex items-center gap-1.5">
                                        <span class="truncate text-sm font-medium">{{ $document->name }}</span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] flex-shrink-0">
                                            {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$document->document_type] ?? $document->document_type }}
                                        </span>
                                    </div>
                                </x-ui-sidebar-item>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-ui-sidebar-list>
        @endforeach

        {{-- Unverknuepfte Dokumente --}}
        @if($unlinkedDocuments->isNotEmpty())
            <x-ui-sidebar-list label="Unverknuepft">
                @foreach($unlinkedDocuments as $document)
                    <x-ui-sidebar-item :href="route('specs.documents.show', ['document' => $document])" :title="$document->name">
                        @svg('heroicon-o-document-text', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                        <div class="flex-1 min-w-0 ml-2 flex items-center gap-1.5">
                            <span class="truncate text-sm font-medium">{{ $document->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] flex-shrink-0">
                                {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$document->document_type] ?? $document->document_type }}
                            </span>
                        </div>
                    </x-ui-sidebar-item>
                @endforeach
            </x-ui-sidebar-list>
        @endif

        {{-- Toggle: All/My --}}
        @if($hasMoreDocuments)
            <div class="px-3 py-2">
                <button type="button" wire:click="toggleShowAllDocuments"
                    x-on:click="localStorage.setItem('specs.showAllDocuments', (!$wire.showAllDocuments).toString())"
                    class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors">
                    @if($showAllDocuments)
                        @svg('heroicon-o-eye-slash', 'w-4 h-4')
                        <span>Nur meine Dokumente</span>
                    @else
                        @svg('heroicon-o-eye', 'w-4 h-4')
                        <span>Alle Dokumente anzeigen</span>
                    @endif
                </button>
            </div>
        @endif
    </div>
</div>
