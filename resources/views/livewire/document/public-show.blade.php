<div class="space-y-6 max-w-4xl mx-auto p-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold">{{ $document->name }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <x-ui-badge :variant="\Platform\Specs\Models\SpecsDocument::STATUS_VARIANTS[$document->status] ?? 'secondary'">
                {{ \Platform\Specs\Models\SpecsDocument::STATUS_LABELS[$document->status] ?? $document->status }}
            </x-ui-badge>
            <span class="text-gray-500 text-sm">{{ \Platform\Specs\Models\SpecsDocument::TYPE_LABELS[$document->document_type] ?? $document->document_type }}</span>
        </div>
    </div>

    @if($document->description)
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-600">{{ $document->description }}</p>
        </div>
    @endif

    {{-- Sections & Requirements (read-only) --}}
    @foreach($document->sections->where('parent_id', null) as $section)
        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-lg font-semibold">{{ $section->position }}. {{ $section->title }}</h2>
            @if($section->description)
                <p class="text-gray-500 text-sm">{{ $section->description }}</p>
            @endif

            @foreach($document->sections->where('parent_id', $section->id) as $subSection)
                <div class="ml-4 border-l-2 border-gray-200 pl-4 space-y-2">
                    <h3 class="font-semibold">{{ $section->position }}.{{ $subSection->position }} {{ $subSection->title }}</h3>
                    @include('specs::livewire.document._public-requirements', ['requirements' => $subSection->requirements])
                </div>
            @endforeach

            @include('specs::livewire.document._public-requirements', ['requirements' => $section->requirements])
        </div>
    @endforeach

    {{-- Kommentare --}}
    @if($document->comments->count() > 0)
        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-lg font-semibold">Kommentare ({{ $document->comments->count() }})</h2>
            @foreach($document->comments->whereNull('parent_id') as $comment)
                <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                    <p>{{ $comment->content }}</p>
                    <div class="text-gray-400 text-xs">{{ $comment->created_at?->diffForHumans() }}</div>
                    @foreach($comment->replies as $reply)
                        <div class="ml-4 bg-white rounded-lg p-2 border border-gray-100">
                            <p class="text-sm">{{ $reply->content }}</p>
                            <div class="text-gray-400 text-xs">{{ $reply->created_at?->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>
