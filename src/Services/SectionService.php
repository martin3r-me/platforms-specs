<?php

namespace Platform\Specs\Services;

use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsSection;

class SectionService
{
    public function createSection(SpecsDocument $document, array $data): SpecsSection
    {
        if (!isset($data['position'])) {
            $query = $document->sections();
            if (isset($data['parent_id'])) {
                $query->where('parent_id', $data['parent_id']);
            } else {
                $query->whereNull('parent_id');
            }
            $data['position'] = ($query->max('position') ?? 0) + 1;
        }

        return $document->sections()->create($data);
    }

    public function reorderSections(SpecsDocument $document, array $sectionIds): void
    {
        foreach ($sectionIds as $position => $sectionId) {
            $document->sections()
                ->where('id', $sectionId)
                ->update(['position' => $position + 1]);
        }
    }
}
