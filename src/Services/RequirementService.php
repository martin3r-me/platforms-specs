<?php

namespace Platform\Specs\Services;

use Platform\Specs\Models\SpecsRequirement;
use Platform\Specs\Models\SpecsSection;

class RequirementService
{
    public function createRequirement(SpecsSection $section, array $data): SpecsRequirement
    {
        if (!isset($data['position'])) {
            $data['position'] = ($section->requirements()->max('position') ?? 0) + 1;
        }

        // Generate requirement ID from the document
        if (!isset($data['requirement_id'])) {
            $document = $section->document;
            $data['requirement_id'] = $document->generateRequirementId();
        }

        return $section->requirements()->create($data);
    }

    /**
     * @return array<SpecsRequirement>
     */
    public function bulkCreateRequirements(SpecsSection $section, array $requirementsData, int $userId): array
    {
        $document = $section->document;
        $maxPosition = $section->requirements()->max('position') ?? 0;
        $created = [];

        foreach ($requirementsData as $data) {
            $maxPosition++;
            $created[] = $section->requirements()->create([
                'requirement_id' => $document->generateRequirementId(),
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'requirement_type' => $data['requirement_type'] ?? 'functional',
                'priority' => $data['priority'] ?? 'should',
                'status' => $data['status'] ?? 'draft',
                'position' => $data['position'] ?? $maxPosition,
                'metadata' => $data['metadata'] ?? null,
                'created_by_user_id' => $userId,
            ]);
        }

        return $created;
    }

    public function reorderRequirements(SpecsSection $section, array $requirementIds): void
    {
        foreach ($requirementIds as $position => $requirementId) {
            $section->requirements()
                ->where('id', $requirementId)
                ->update(['position' => $position + 1]);
        }
    }
}
