@if($requirements->count() > 0)
    <div class="space-y-2">
        @foreach($requirements as $req)
            <div class="bg-muted-5 rounded-lg p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex gap-2 align-items-center">
                            <span class="font-mono text-sm font-bold">{{ $req->requirement_id }}</span>
                            <span class="font-semibold">{{ $req->title }}</span>
                        </div>
                        @if($req->content)
                            <p class="text-muted text-sm mt-1">{{ $req->content }}</p>
                        @endif

                        {{-- Metadata fuer User Stories --}}
                        @if($req->requirement_type === 'user_story' && $req->metadata)
                            <div class="text-sm mt-1 text-muted">
                                Als <strong>{{ $req->metadata['role'] ?? '...' }}</strong>
                                moechte ich <strong>{{ $req->metadata['goal'] ?? '...' }}</strong>,
                                damit <strong>{{ $req->metadata['benefit'] ?? '...' }}</strong>.
                            </div>
                        @endif

                        {{-- Acceptance Criteria --}}
                        @if($req->acceptanceCriteria->count() > 0)
                            <div class="mt-2 space-y-1">
                                <div class="text-xs font-semibold text-muted">Abnahmekriterien:</div>
                                @foreach($req->acceptanceCriteria as $ac)
                                    <div class="text-sm text-muted ml-2">- {{ $ac->content }}</div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Traces --}}
                        @if($req->sourceTraces->count() > 0)
                            <div class="mt-1 text-xs text-muted">
                                Verknuepft mit:
                                @foreach($req->sourceTraces as $trace)
                                    <span class="font-mono">{{ $trace->targetRequirement?->requirement_id }}</span>@if(!$loop->last), @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <x-ui-badge variant="{{ $req->priority === 'must' ? 'danger' : ($req->priority === 'should' ? 'warning' : 'secondary') }}">
                            {{ \Platform\Specs\Models\SpecsRequirement::PRIORITY_LABELS[$req->priority] ?? $req->priority }}
                        </x-ui-badge>
                        <x-ui-badge variant="secondary">
                            {{ \Platform\Specs\Models\SpecsRequirement::TYPE_LABELS[$req->requirement_type] ?? $req->requirement_type }}
                        </x-ui-badge>
                        <x-ui-badge variant="{{ $req->status === 'verified' ? 'success' : ($req->status === 'implemented' ? 'info' : 'secondary') }}">
                            {{ \Platform\Specs\Models\SpecsRequirement::STATUS_LABELS[$req->status] ?? $req->status }}
                        </x-ui-badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
