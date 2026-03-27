<div class="space-y-6">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="text-2xl font-bold">Specs - Dashboard</h1>
    </div>

    {{-- Status-Uebersicht --}}
    <div class="grid grid-cols-5 gap-4">
        @foreach(\Platform\Specs\Models\SpecsDocument::STATUSES as $status)
            <div class="bg-muted-5 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold">{{ $statusCounts[$status] ?? 0 }}</div>
                <div class="text-muted text-sm">{{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$status] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Dokumente --}}
    <div class="space-y-2">
        <h2 class="text-lg font-semibold">Dokumente</h2>

        @forelse($documents as $doc)
            <a href="{{ route('specs.documents.show', $doc) }}" class="d-flex align-items-center gap-3 bg-muted-5 rounded-lg p-4 hover:bg-muted-10 transition">
                <div class="flex-grow-1">
                    <div class="font-semibold">{{ $doc->name }}</div>
                    <div class="text-muted text-sm">
                        {{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$doc->document_type] ?? $doc->document_type }}
                        &middot; {{ $doc->sections_count }} Sections
                        &middot; {{ $doc->comments_count }} Kommentare
                    </div>
                </div>
                <x-ui-badge :variant="\Platform\Specs\Models\SpecsDocument::STATUS_VARIANTS[$doc->status] ?? 'secondary'">
                    {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$doc->status] ?? $doc->status }}
                </x-ui-badge>
            </a>
        @empty
            <div class="bg-muted-5 rounded-lg p-8 text-center text-muted">
                Noch keine Dokumente vorhanden. Erstelle dein erstes Lastenheft oder Pflichtenheft per AI-Assistent.
            </div>
        @endforelse
    </div>
</div>
