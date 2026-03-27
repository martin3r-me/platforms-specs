<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsRequirement;

class Requirements extends Component
{
    use WithPagination;

    public SpecsDocument $document;
    public string $typeFilter = '';
    public string $priorityFilter = '';
    public string $statusFilter = '';
    public string $search = '';

    public function mount(SpecsDocument $document)
    {
        $this->document = $document;
    }

    public function render()
    {
        $sectionIds = $this->document->sections()->pluck('id');

        $query = SpecsRequirement::query()
            ->whereIn('section_id', $sectionIds)
            ->with(['section', 'acceptanceCriteria', 'sourceTraces.targetRequirement']);

        if ($this->typeFilter) {
            $query->where('requirement_type', $this->typeFilter);
        }
        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('content', 'like', "%{$this->search}%")
                ->orWhere('requirement_id', 'like', "%{$this->search}%"));
        }

        $requirements = $query->orderBy('requirement_id')->paginate(50);

        // Stats
        $totalCount = SpecsRequirement::whereIn('section_id', $sectionIds)->count();
        $priorityCounts = SpecsRequirement::whereIn('section_id', $sectionIds)
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        $statusCounts = SpecsRequirement::whereIn('section_id', $sectionIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('specs::livewire.document.requirements', [
            'requirements' => $requirements,
            'totalCount' => $totalCount,
            'priorityCounts' => $priorityCounts,
            'statusCounts' => $statusCounts,
        ])->layout('platform::layouts.app');
    }
}
