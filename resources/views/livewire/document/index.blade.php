<div class="space-y-6">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="text-2xl font-bold">Dokumente</h1>
    </div>

    {{-- Filter --}}
    <div class="d-flex gap-3">
        <x-ui-input-select wire:model.live="typeFilter" placeholder="Alle Typen">
            <option value="">Alle Typen</option>
            @foreach(\Platform\Specs\Models\SpecsDocument::DOCUMENT_TYPES as $type)
                <option value="{{ $type }}">{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$type] }}</option>
            @endforeach
        </x-ui-input-select>

        <x-ui-input-select wire:model.live="statusFilter" placeholder="Alle Status">
            <option value="">Alle Status</option>
            @foreach(\Platform\Specs\Models\SpecsDocument::STATUSES as $status)
                <option value="{{ $status }}">{{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$status] }}</option>
            @endforeach
        </x-ui-input-select>

        <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Suchen..." />
    </div>

    {{-- Tabelle --}}
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
                        <div class="text-muted text-sm">{{ Str::limit($doc->description, 80) }}</div>
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
                <td class="text-muted text-sm">{{ $doc->updated_at?->diffForHumans() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted p-8">Keine Dokumente gefunden.</td>
            </tr>
        @endforelse
    </x-ui-table>

    {{ $documents->links() }}
</div>
