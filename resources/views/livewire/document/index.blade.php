<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dokumente" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'href' => route('specs.dashboard'), 'icon' => 'document-text'],
            ['label' => 'Dokumente'],
        ]">
            <x-slot name="left">
                <x-ui-input-select wire:model.live="typeFilter" class="!h-7 !text-xs !py-0">
                    <option value="">Alle Typen</option>
                    @foreach(\Platform\Specs\Models\SpecsDocument::DOCUMENT_TYPES as $type)
                        <option value="{{ $type }}">{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$type] }}</option>
                    @endforeach
                </x-ui-input-select>

                <x-ui-input-select wire:model.live="statusFilter" class="!h-7 !text-xs !py-0">
                    <option value="">Alle Status</option>
                    @foreach(\Platform\Specs\Models\SpecsDocument::STATUSES as $status)
                        <option value="{{ $status }}">{{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$status] }}</option>
                    @endforeach
                </x-ui-input-select>

                <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Suchen..." class="!h-7 !text-xs !py-0" />
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        <x-ui-table>
            <x-slot name="header">
                <th>Name</th>
                <th>Typ</th>
                <th>Status</th>
                <th>Sections</th>
                <th>Kommentare</th>
                <th>Aktualisiert</th>
            </x-slot>
            @forelse($documents as $doc)
                <tr>
                    <td>
                        <a href="{{ route('specs.documents.show', $doc) }}" class="font-semibold">
                            {{ $doc->name }}
                        </a>
                        @if($doc->description)
                            <div class="text-[var(--ui-muted)] text-sm">{{ Str::limit($doc->description, 80) }}</div>
                        @endif
                    </td>
                    <td>{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type }}</td>
                    <td>
                        <x-ui-badge :variant="\Platform\Specs\Models\SpecsDocument::STATUS_VARIANTS[$doc->status] ?? 'secondary'">
                            {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$doc->status] ?? $doc->status }}
                        </x-ui-badge>
                    </td>
                    <td>{{ $doc->sections_count }}</td>
                    <td>{{ $doc->comments_count }}</td>
                    <td class="text-[var(--ui-muted)] text-sm">{{ $doc->updated_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-[var(--ui-muted)] p-8">Keine Dokumente gefunden.</td>
                </tr>
            @endforelse
        </x-ui-table>

        {{ $documents->links() }}
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="false">
            <div class="p-5 space-y-4">
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Typ</label>
                    @foreach(\Platform\Specs\Models\SpecsDocument::DOCUMENT_TYPES as $type)
                        <button wire:click="$set('typeFilter', '{{ $typeFilter === $type ? '' : $type }}')"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition
                                    {{ $typeFilter === $type ? 'bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))]' : 'hover:bg-[var(--ui-muted-5)]' }}">
                            @svg('heroicon-o-document-text', 'w-4 h-4')
                            {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$type] }}
                        </button>
                    @endforeach
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Status</label>
                    @foreach(\Platform\Specs\Models\SpecsDocument::STATUSES as $status)
                        <button wire:click="$set('statusFilter', '{{ $statusFilter === $status ? '' : $status }}')"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition
                                    {{ $statusFilter === $status ? 'bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))]' : 'hover:bg-[var(--ui-muted-5)]' }}">
                            @svg(\Platform\Specs\Models\SpecsDocument::STATUS_ICONS[$status], 'w-4 h-4')
                            {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$status] }}
                        </button>
                    @endforeach
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
