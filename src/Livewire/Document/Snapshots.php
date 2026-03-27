<?php

namespace Platform\Specs\Livewire\Document;

use Livewire\Component;
use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsDocumentSnapshot;
use Platform\Specs\Services\DocumentService;

class Snapshots extends Component
{
    public SpecsDocument $document;
    public ?int $compareFrom = null;
    public ?int $compareTo = null;
    public ?array $comparison = null;

    public function mount(SpecsDocument $document)
    {
        $this->document = $document;
    }

    public function compare()
    {
        if (!$this->compareFrom || !$this->compareTo) {
            return;
        }

        $documentService = new DocumentService();
        $this->comparison = $documentService->compareSnapshots($this->compareFrom, $this->compareTo);
    }

    public function resetComparison()
    {
        $this->compareFrom = null;
        $this->compareTo = null;
        $this->comparison = null;
    }

    public function render()
    {
        $snapshots = $this->document->snapshots()
            ->with('createdByUser')
            ->orderBy('version', 'desc')
            ->get();

        return view('specs::livewire.document.snapshots', [
            'snapshots' => $snapshots,
        ])->layout('platform::layouts.app');
    }
}
