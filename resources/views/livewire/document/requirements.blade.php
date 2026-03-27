<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Anforderungen" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Specs', 'href' => route('specs.dashboard'), 'icon' => 'document-text'],
            ['label' => 'Dokumente', 'href' => route('specs.documents.index')],
            ['label' => $document->name, 'href' => route('specs.documents.show', $document)],
            ['label' => 'Anforderungen'],
        ]">
            <x-slot name="left">
                <x-ui-input-select wire:model.live="typeFilter" class="!h-7 !text-xs !py-0">
                    <option value="">Alle Typen</option>
                    @foreach(\Platform\Specs\Models\SpecsRequirement::TYPES as $type)
                        <option value="{{ $type }}">{{ \Platform\Specs\Models\SpecsRequirement::TYPE_LABELS[$type] ?? $type }}</option>
                    @endforeach
                </x-ui-input-select>

                <x-ui-input-select wire:model.live="priorityFilter" class="!h-7 !text-xs !py-0">
                    <option value="">Alle Prioritaeten</option>
                    @foreach(\Platform\Specs\Models\SpecsRequirement::PRIORITIES as $p)
                        <option value="{{ $p }}">{{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$p] ?? $p }}</option>
                    @endforeach
                </x-ui-input-select>

                <x-ui-input-select wire:model.live="statusFilter" class="!h-7 !text-xs !py-0">
                    <option value="">Alle Status</option>
                    @foreach(\Platform\Specs\Models\SpecsRequirement::STATUSES as $s)
                        <option value="{{ $s }}">{{ \Platform\Specs\Models\SpecsRequirement::STATUS_LABELS[$s] ?? $s }}</option>
                    @endforeach
                </x-ui-input-select>

                <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Suchen..." class="!h-7 !text-xs !py-0" />
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        <x-ui-table>
            <x-slot name="header">
                <th>ID</th>
                <th>Titel</th>
                <th>Section</th>
                <th>Typ</th>
                <th>Prioritaet</th>
                <th>Status</th>
                <th>AK</th>
                <th>Traces</th>
            </x-slot>
            @forelse($requirements as $req)
                <tr>
                    <td class="font-mono text-sm font-bold whitespace-nowrap">{{ $req->requirement_id }}</td>
                    <td>
                        <div class="font-semibold">{{ $req->title }}</div>
                        @if($req->content)
                            <div class="text-[var(--ui-muted)] text-sm">{{ Str::limit($req->content, 100) }}</div>
                        @endif
                        @if($req->requirement_type === 'user_story' && $req->metadata)
                            <div class="text-[var(--ui-muted)] text-xs mt-0.5">
                                Als <strong>{{ $req->metadata['role'] ?? '...' }}</strong>
                                moechte ich <strong>{{ Str::limit($req->metadata['goal'] ?? '...', 40) }}</strong>
                            </div>
                        @endif
                    </td>
                    <td class="text-sm text-[var(--ui-muted)]">{{ $req->section?->title ?? '-' }}</td>
                    <td>
                        <x-ui-badge variant="secondary">
                            {{ \Platform\Specs\Models\SpecsRequirement::TYPE_LABELS[$req->requirement_type] ?? $req->requirement_type }}
                        </x-ui-badge>
                    </td>
                    <td>
                        <x-ui-badge variant="{{ $req->priority === 'must' ? 'danger' : ($req->priority === 'should' ? 'warning' : 'secondary') }}">
                            {{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$req->priority] ?? $req->priority }}
                        </x-ui-badge>
                    </td>
                    <td>
                        <x-ui-badge variant="{{ $req->status === 'verified' ? 'success' : ($req->status === 'implemented' ? 'info' : ($req->status === 'approved' ? 'warning' : 'secondary')) }}">
                            {{ \Platform\Specs\Models\SpecsRequirement::STATUS_LABELS[$req->status] ?? $req->status }}
                        </x-ui-badge>
                    </td>
                    <td class="text-center text-sm">{{ $req->acceptanceCriteria->count() }}</td>
                    <td class="text-center text-sm">{{ $req->sourceTraces->count() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-[var(--ui-muted)] p-8">Keine Anforderungen gefunden.</td>
                </tr>
            @endforelse
        </x-ui-table>

        {{ $requirements->links() }}
    </x-ui-page-container>

    {{-- Left Sidebar: Stats --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Uebersicht" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Gesamt</h3>
                    <span class="text-2xl font-bold">{{ $totalCount }}</span>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Nach Prioritaet</h3>
                    @foreach(\Platform\Specs\Models\SpecsRequirement::PRIORITIES as $p)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--ui-muted)]">{{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$p] ?? $p }}</span>
                            <span class="font-semibold">{{ $priorityCounts[$p] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wide">Nach Status</h3>
                    @foreach(\Platform\Specs\Models\SpecsRequirement::STATUSES as $s)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--ui-muted)]">{{ \Platform\Specs\Models\SpecsRequirement::STATUS_LABELS[$s] ?? $s }}</span>
                            <span class="font-semibold">{{ $statusCounts[$s] ?? 0 }}</span>
                        </div>
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
