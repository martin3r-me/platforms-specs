@if($requirements->count() > 0)
    <div class="space-y-2">
        @foreach($requirements as $req)
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-bold">{{ $req->requirement_id }}</span>
                            <span class="font-semibold">{{ $req->title }}</span>
                        </div>
                        @if($req->content)
                            <p class="text-gray-500 text-sm mt-1">{{ $req->content }}</p>
                        @endif

                        @if($req->requirement_type === 'user_story' && $req->metadata)
                            <div class="text-sm mt-1 text-gray-500">
                                Als <strong>{{ $req->metadata['role'] ?? '...' }}</strong>
                                moechte ich <strong>{{ $req->metadata['goal'] ?? '...' }}</strong>,
                                damit <strong>{{ $req->metadata['benefit'] ?? '...' }}</strong>.
                            </div>
                        @endif

                        @if($req->acceptanceCriteria->count() > 0)
                            <div class="mt-2 space-y-1">
                                <div class="text-xs font-semibold text-gray-500">Abnahmekriterien:</div>
                                @foreach($req->acceptanceCriteria as $ac)
                                    <div class="text-sm text-gray-500 ml-2">- {{ $ac->content }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <x-ui-badge variant="{{ $req->priority === 'must' ? 'danger' : ($req->priority === 'should' ? 'warning' : 'secondary') }}">
                            {{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$req->priority] ?? $req->priority }}
                        </x-ui-badge>
                        <x-ui-badge variant="secondary">
                            {{ \Platform\Specs\Models\SpecsRequirement::TYPE_LABELS[$req->requirement_type] ?? $req->requirement_type }}
                        </x-ui-badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
