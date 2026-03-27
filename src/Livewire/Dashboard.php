<?php

namespace Platform\Specs\Livewire;

use Livewire\Component;
use Platform\Specs\Models\SpecsDocument;

class Dashboard extends Component
{
    public function render()
    {
        $teamId = auth()->user()?->current_team_id;

        $documents = SpecsDocument::query()
            ->forTeam($teamId)
            ->withCount('sections', 'comments')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        $statusCounts = SpecsDocument::query()
            ->forTeam($teamId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $typeCounts = SpecsDocument::query()
            ->forTeam($teamId)
            ->selectRaw('document_type, count(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        $totalDocuments = array_sum($statusCounts);

        return view('specs::livewire.dashboard', [
            'documents' => $documents,
            'statusCounts' => $statusCounts,
            'typeCounts' => $typeCounts,
            'totalDocuments' => $totalDocuments,
        ])->layout('platform::layouts.app');
    }
}
