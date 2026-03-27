<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Snapshots" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'href' => route('specs.dashboard'), 'icon' => 'document-text'],
            ['label' => 'Dokumente', 'href' => route('specs.documents.index')],
            ['label' => $document->name, 'href' => route('specs.documents.show', $document)],
            ['label' => 'Snapshots'],
        ]">
            <x-slot name="left">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold
                    bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))]
                    border border-[rgb(var(--ui-primary-rgb))]/20">
                    @svg('heroicon-o-camera', 'w-3.5 h-3.5')
                    {{ $snapshots->count() }} Snapshots
                </span>
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Vergleich --}}
        @if($comparison)
            <x-ui-panel title="Vergleich" subtitle="Version {{ $comparison['from_version'] ?? '?' }} vs. Version {{ $comparison['to_version'] ?? '?' }}">
                <div class="space-y-3">
                    @if(!empty($comparison['changes']))
                        @foreach($comparison['changes'] as $change)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 text-sm">
                                <div class="font-semibold">{{ $change['field'] ?? 'Aenderung' }}</div>
                                @if(isset($change['from']))
                                    <div class="text-red-600 line-through">{{ is_array($change['from']) ? json_encode($change['from']) : $change['from'] }}</div>
                                @endif
                                @if(isset($change['to']))
                                    <div class="text-green-600">{{ is_array($change['to']) ? json_encode($change['to']) : $change['to'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-[var(--ui-muted)] text-sm">Keine Aenderungen gefunden.</div>
                    @endif
                </div>
                <div class="mt-3">
                    <x-ui-button variant="ghost" size="sm" wire:click="resetComparison">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Vergleich schliessen
                    </x-ui-button>
                </div>
            </x-ui-panel>
        @else
            {{-- Vergleich starten --}}
            @if($snapshots->count() >= 2)
                <x-ui-panel title="Versionen vergleichen">
                    <div class="flex items-center gap-3">
                        <x-ui-input-select name="compareFrom" wire:model="compareFrom">
                            <option value="">Von...</option>
                            @foreach($snapshots as $snap)
                                <option value="{{ $snap->id }}">Version {{ $snap->version }} ({{ $snap->created_at?->format('d.m.Y H:i') }})</option>
                            @endforeach
                        </x-ui-input-select>

                        @svg('heroicon-o-arrow-right', 'w-5 h-5 text-[var(--ui-muted)] flex-shrink-0')

                        <x-ui-input-select name="compareTo" wire:model="compareTo">
                            <option value="">Bis...</option>
                            @foreach($snapshots as $snap)
                                <option value="{{ $snap->id }}">Version {{ $snap->version }} ({{ $snap->created_at?->format('d.m.Y H:i') }})</option>
                            @endforeach
                        </x-ui-input-select>

                        <x-ui-button variant="primary" size="sm" wire:click="compare">
                            @svg('heroicon-o-arrows-right-left', 'w-4 h-4')
                            Vergleichen
                        </x-ui-button>
                    </div>
                </x-ui-panel>
            @endif
        @endif

        {{-- Snapshot-Liste --}}
        <x-ui-table>
            <x-slot name="header">
                <th>Version</th>
                <th>Erstellt am</th>
                <th>Erstellt von</th>
                <th>Sections</th>
                <th>Requirements</th>
            </x-slot>
            @forelse($snapshots as $snap)
                @php
                    $data = $snap->snapshot_data ?? [];
                    $sectionsCount = count($data['sections'] ?? []);
                    $reqCount = collect($data['sections'] ?? [])->sum(fn($s) => count($s['requirements'] ?? []));
                @endphp
                <tr>
                    <td>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-[var(--ui-muted-5)]">
                            v{{ $snap->version }}
                        </span>
                    </td>
                    <td class="text-sm">{{ $snap->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="text-sm text-[var(--ui-muted)]">{{ $snap->createdByUser?->name ?? '-' }}</td>
                    <td class="text-sm text-center">{{ $sectionsCount }}</td>
                    <td class="text-sm text-center">{{ $reqCount }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-[var(--ui-muted)] p-8">
                        Keine Snapshots vorhanden. Erstelle einen Snapshot per AI-Assistent.
                    </td>
                </tr>
            @endforelse
        </x-ui-table>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Dokument</h3>
                    <a href="{{ route('specs.documents.show', $document) }}" class="text-sm text-[var(--ui-primary)] hover:underline">
                        {{ $document->name }}
                    </a>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Snapshots</h3>
                    <span class="text-2xl font-bold">{{ $snapshots->count() }}</span>
                </div>
                @if($snapshots->isNotEmpty())
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Letzter Snapshot</h3>
                        <span class="text-sm text-[var(--ui-muted)]">{{ $snapshots->first()?->created_at?->diffForHumans() }}</span>
                    </div>
                @endif
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
