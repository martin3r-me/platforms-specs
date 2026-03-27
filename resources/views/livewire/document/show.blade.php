<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'href' => route('specs.dashboard'), 'icon' => 'document-text'],
            ['label' => 'Dokumente', 'href' => route('specs.documents.index')],
            ['label' => $document->name],
        ]">
            <x-slot name="left">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold
                    bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))]
                    border border-[rgb(var(--ui-primary-rgb))]/20">
                    @svg('heroicon-o-document-text', 'w-3.5 h-3.5')
                    {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$document->document_type] ?? $document->document_type }}
                </span>
                <span class="text-xs text-[var(--ui-muted)]">Prefix: {{ $document->prefix }}</span>
                @if($document->linkedDocument)
                    <a href="{{ route('specs.documents.show', $document->linkedDocument) }}"
                       class="inline-flex items-center gap-1 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition">
                        @svg('heroicon-o-link', 'w-3.5 h-3.5')
                        {{ $document->linkedDocument->name }}
                    </a>
                @endif
                @if($document->is_public && $document->public_token)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-green-500/10 text-green-600 border border-green-500/20">
                        @svg('heroicon-o-globe-alt', 'w-3 h-3')
                        Public
                    </span>
                @endif
            </x-slot>

            {{-- Rechts: Sub-Navigation --}}
            <a href="{{ route('specs.documents.requirements', $document) }}">
                <x-ui-button variant="ghost" size="sm">
                    @svg('heroicon-o-clipboard-document-list', 'w-4 h-4')
                    <span>Anforderungen ({{ $requirementCount }})</span>
                </x-ui-button>
            </a>
            <a href="{{ route('specs.documents.traces', $document) }}">
                <x-ui-button variant="ghost" size="sm">
                    @svg('heroicon-o-arrows-right-left', 'w-4 h-4')
                    <span>Traces</span>
                </x-ui-button>
            </a>
            <a href="{{ route('specs.documents.snapshots', $document) }}">
                <x-ui-button variant="ghost" size="sm">
                    @svg('heroicon-o-camera', 'w-4 h-4')
                    <span>Snapshots</span>
                </x-ui-button>
            </a>
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        @if($document->description)
            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4">
                <p class="text-[var(--ui-muted)]">{{ $document->description }}</p>
            </div>
        @endif

        {{-- Sections & Requirements --}}
        @foreach($document->sections->where('parent_id', null) as $section)
            <div class="border border-[var(--ui-border)]/60 rounded-lg p-4 space-y-3">
                <h2 class="text-lg font-semibold">{{ $section->position }}. {{ $section->title }}</h2>
                @if($section->description)
                    <p class="text-[var(--ui-muted)] text-sm">{{ $section->description }}</p>
                @endif

                {{-- Sub-Sections --}}
                @foreach($document->sections->where('parent_id', $section->id) as $subSection)
                    <div class="ml-4 border-l-2 border-[var(--ui-border)]/60 pl-4 space-y-2">
                        <h3 class="font-semibold">{{ $section->position }}.{{ $subSection->position }} {{ $subSection->title }}</h3>
                        @include('specs::livewire.document._requirements', ['requirements' => $subSection->requirements])
                    </div>
                @endforeach

                {{-- Requirements der Section --}}
                @include('specs::livewire.document._requirements', ['requirements' => $section->requirements])
            </div>
        @endforeach
    </x-ui-page-container>

    {{-- Left Sidebar: Document Info --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Dokument Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Status --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Status</h3>
                    <div class="flex items-center gap-2">
                        @svg(\Platform\Specs\Models\SpecsDocument::STATUS_ICONS[$document->status] ?? 'heroicon-o-question-mark-circle', 'w-5 h-5 text-[var(--ui-secondary)]')
                        <x-ui-badge :variant="\Platform\Specs\Models\SpecsDocument::STATUS_VARIANTS[$document->status] ?? 'secondary'">
                            {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$document->status] ?? $document->status }}
                        </x-ui-badge>
                    </div>
                </div>

                {{-- Typ --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Typ</h3>
                    <span class="text-sm">{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$document->document_type] ?? $document->document_type }}</span>
                </div>

                {{-- Prefix --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Prefix</h3>
                    <span class="text-sm font-mono">{{ $document->prefix }}</span>
                </div>

                {{-- Sections --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Sections</h3>
                    <span class="text-sm">{{ $document->sections->count() }}</span>
                </div>

                {{-- Requirements --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Anforderungen</h3>
                    <span class="text-sm">{{ $requirementCount }}</span>
                </div>

                {{-- Verknuepftes Dokument --}}
                @if($document->linkedDocument)
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Verknuepft mit</h3>
                        <a href="{{ route('specs.documents.show', $document->linkedDocument) }}"
                           class="text-sm text-[var(--ui-primary)] hover:underline">
                            {{ $document->linkedDocument->name }}
                        </a>
                    </div>
                @endif

                {{-- Erstellt --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Erstellt</h3>
                    <span class="text-sm text-[var(--ui-muted)]">{{ $document->created_at?->format('d.m.Y H:i') }}</span>
                </div>

                {{-- Aktualisiert --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Aktualisiert</h3>
                    <span class="text-sm text-[var(--ui-muted)]">{{ $document->updated_at?->diffForHumans() }}</span>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar: Comments --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Kommentare ({{ $document->comments->count() }})" width="w-96" :defaultOpen="false"
            storeKey="activityOpen" side="right">
            <div class="p-5 space-y-4">
                @forelse($document->comments->whereNull('parent_id') as $comment)
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 space-y-2">
                        <p class="text-sm">{{ $comment->content }}</p>
                        <div class="text-[var(--ui-muted)] text-xs">{{ $comment->created_at?->diffForHumans() }}</div>
                        @foreach($comment->replies as $reply)
                            <div class="ml-4 bg-[var(--ui-surface)] rounded-lg p-2 border border-[var(--ui-border)]/40">
                                <p class="text-sm">{{ $reply->content }}</p>
                                <div class="text-[var(--ui-muted)] text-xs">{{ $reply->created_at?->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="text-center text-[var(--ui-muted)] text-sm">
                        Keine Kommentare vorhanden.
                    </div>
                @endforelse
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
