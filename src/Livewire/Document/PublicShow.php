<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Platform\Specs\Models\SpecsDocument;

class PublicShow extends Component
{
    public SpecsDocument $document;

    public function mount(string $token)
    {
        $this->document = SpecsDocument::query()
            ->where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();
    }

    public function render()
    {
        $this->document->loadMissing([
            'sections.requirements.acceptanceCriteria',
            'comments.replies',
        ]);

        return view('specs::livewire.document.public-show', [
            'document' => $this->document,
        ])->layout('platform::layouts.guest');
    }
}
