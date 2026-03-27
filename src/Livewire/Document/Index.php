<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Specs\Models\SpecsDocument;

class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $search = '';

    public function render()
    {
        $teamId = auth()->user()?->current_team_id;

        $query = SpecsDocument::query()
            ->forTeam($teamId)
            ->withCount('sections', 'comments', 'snapshots');

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }
        if ($this->typeFilter) {
            $query->byType($this->typeFilter);
        }
        if ($this->search) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%"));
        }

        $documents = $query->orderBy('updated_at', 'desc')->paginate(20);

        return view('specs::livewire.document.index', [
            'documents' => $documents,
        ])->layout('platform::layouts.app');
    }
}
