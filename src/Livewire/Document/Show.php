<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Platform\Specs\Models\SpecsDocument;

class Show extends Component
{
    public SpecsDocument $document;

    public function mount(SpecsDocument $document)
    {
        $this->document = $document;
    }

    public function render()
    {
        $this->document->loadMissing([
            'sections.requirements.acceptanceCriteria',
            'sections.requirements.sourceTraces.targetRequirement',
            'sections.requirements.targetTraces.sourceRequirement',
            'linkedDocument',
            'comments.replies',
        ]);

        return view('specs::livewire.document.show', [
            'document' => $this->document,
        ])->layout('platform::layouts.app');
    }
}
