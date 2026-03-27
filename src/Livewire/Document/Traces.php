<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsRequirement;
use Platform\Specs\Models\SpecsTrace;
use Platform\Specs\Services\CoverageService;

class Traces extends Component
{
    public SpecsDocument $document;

    public function mount(SpecsDocument $document)
    {
        $this->document = $document;
    }

    public function render()
    {
        $this->document->loadMissing(['linkedDocument', 'sections.requirements']);

        // Alle Requirements dieses Dokuments
        $sectionIds = $this->document->sections->pluck('id');
        $requirements = SpecsRequirement::whereIn('section_id', $sectionIds)
            ->with(['sourceTraces.targetRequirement.section', 'targetTraces.sourceRequirement.section'])
            ->orderBy('requirement_id')
            ->get();

        // Coverage-Analyse falls verknüpftes Dokument existiert
        $coverage = null;
        if ($this->document->linkedDocument) {
            $coverageService = new CoverageService();
            $coverage = $coverageService->analyzeCoverage($this->document->id);
        }

        // Traces für dieses Dokument
        $traces = SpecsTrace::query()
            ->whereIn('source_requirement_id', $requirements->pluck('id'))
            ->orWhereIn('target_requirement_id', $requirements->pluck('id'))
            ->with(['sourceRequirement.section', 'targetRequirement.section'])
            ->get();

        return view('specs::livewire.document.traces', [
            'requirements' => $requirements,
            'traces' => $traces,
            'coverage' => $coverage,
        ])->layout('platform::layouts.app');
    }
}
