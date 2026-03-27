<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Traceability" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'href' => route('specs.dashboard'), 'icon' => 'document-text'],
            ['label' => 'Dokumente', 'href' => route('specs.documents.index')],
            ['label' => $document->name, 'href' => route('specs.documents.show', $document)],
            ['label' => 'Traceability'],
        ]">
            <x-slot name="left">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold
                    bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))]
                    border border-[rgb(var(--ui-primary-rgb))]/20">
                    @svg('heroicon-o-arrows-right-left', 'w-3.5 h-3.5')
                    {{ $traces->count() }} Traces
                </span>
                @if($document->linkedDocument)
                    <span class="text-xs text-[var(--ui-muted)]">
                        @svg('heroicon-o-link', 'w-3.5 h-3.5 inline')
                        {{ $document->linkedDocument->name }}
                    </span>
                @endif
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Coverage-Analyse --}}
        @if($coverage)
            <x-ui-panel title="Coverage-Analyse" subtitle="Abdeckung der Anforderungen zwischen verknuepften Dokumenten">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold">{{ $coverage['total_source_requirements'] ?? 0 }}</div>
                        <div class="text-[var(--ui-muted)] text-sm">Quell-Anforderungen</div>
                    </div>
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold">{{ $coverage['covered_count'] ?? 0 }}</div>
                        <div class="text-[var(--ui-muted)] text-sm">Abgedeckt</div>
                    </div>
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold {{ ($coverage['coverage_percentage'] ?? 0) >= 80 ? 'text-green-600' : (($coverage['coverage_percentage'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($coverage['coverage_percentage'] ?? 0, 1) }}%
                        </div>
                        <div class="text-[var(--ui-muted)] text-sm">Coverage</div>
                    </div>
                </div>

                {{-- Nicht abgedeckte Anforderungen --}}
                @if(!empty($coverage['uncovered_requirements']))
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-red-600 mb-2">Nicht abgedeckte Anforderungen</h4>
                        <div class="space-y-1">
                            @foreach($coverage['uncovered_requirements'] as $uncovered)
                                <div class="flex items-center gap-2 text-sm bg-red-50 dark:bg-red-900/10 rounded p-2">
                                    <span class="font-mono font-bold">{{ $uncovered['requirement_id'] ?? '' }}</span>
                                    <span>{{ $uncovered['title'] ?? '' }}</span>
                                    <x-ui-badge variant="{{ ($uncovered['priority'] ?? '') === 'must' ? 'danger' : 'secondary' }}">
                                        {{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$uncovered['priority'] ?? ''] ?? $uncovered['priority'] ?? '-' }}
                                    </x-ui-badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-ui-panel>
        @endif

        {{-- Trace-Tabelle --}}
        <x-ui-panel title="Traces" subtitle="Verknuepfungen zwischen Anforderungen">
            <x-ui-table>
                <x-slot name="header">
                    <th>Quelle</th>
                    <th>Titel (Quelle)</th>
                    <th></th>
                    <th>Ziel</th>
                    <th>Titel (Ziel)</th>
                    <th>Beschreibung</th>
                </x-slot>
                @forelse($traces as $trace)
                    <tr>
                        <td class="font-mono text-sm font-bold whitespace-nowrap">{{ $trace->sourceRequirement?->requirement_id ?? '-' }}</td>
                        <td class="text-sm">{{ $trace->sourceRequirement?->title ?? '-' }}</td>
                        <td class="text-center text-[var(--ui-muted)]">@svg('heroicon-o-arrow-right', 'w-4 h-4 inline')</td>
                        <td class="font-mono text-sm font-bold whitespace-nowrap">{{ $trace->targetRequirement?->requirement_id ?? '-' }}</td>
                        <td class="text-sm">{{ $trace->targetRequirement?->title ?? '-' }}</td>
                        <td class="text-sm text-[var(--ui-muted)]">{{ $trace->description ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-[var(--ui-muted)] p-8">Keine Traces vorhanden.</td>
                    </tr>
                @endforelse
            </x-ui-table>
        </x-ui-panel>

        {{-- Anforderungen ohne Traces --}}
        @php
            $tracedIds = $traces->pluck('source_requirement_id')->merge($traces->pluck('target_requirement_id'))->unique();
            $untracedRequirements = $requirements->filter(fn($r) => !$tracedIds->contains($r->id));
        @endphp
        @if($untracedRequirements->isNotEmpty())
            <x-ui-panel title="Ohne Traces ({{ $untracedRequirements->count() }})" subtitle="Anforderungen ohne Verknuepfung">
                <div class="space-y-1">
                    @foreach($untracedRequirements as $req)
                        <div class="flex items-center gap-3 p-2 rounded hover:bg-[var(--ui-muted-5)] text-sm">
                            <span class="font-mono font-bold whitespace-nowrap">{{ $req->requirement_id }}</span>
                            <span class="flex-1 min-w-0 truncate">{{ $req->title }}</span>
                            <x-ui-badge variant="{{ $req->priority === 'must' ? 'danger' : ($req->priority === 'should' ? 'warning' : 'secondary') }}">
                                {{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$req->priority] ?? $req->priority }}
                            </x-ui-badge>
                        </div>
                    @endforeach
                </div>
            </x-ui-panel>
        @endif
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
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Anforderungen</h3>
                    <span class="text-sm">{{ $requirements->count() }}</span>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Traces</h3>
                    <span class="text-sm">{{ $traces->count() }}</span>
                </div>
                @if($coverage)
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Coverage</h3>
                        <span class="text-sm font-bold {{ ($coverage['coverage_percentage'] ?? 0) >= 80 ? 'text-green-600' : (($coverage['coverage_percentage'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($coverage['coverage_percentage'] ?? 0, 1) }}%
                        </span>
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
