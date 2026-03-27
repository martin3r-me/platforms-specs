<?php

namespace Platform\Specs\Services;

use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsRequirement;
use Platform\Specs\Models\SpecsTrace;

class CoverageService
{
    /**
     * Analyze coverage between a Lastenheft and its linked Pflichtenheft.
     */
    public function analyzeCoverage(SpecsDocument $lastenheft, ?SpecsDocument $pflichtenheft = null): array
    {
        if (!$pflichtenheft && $lastenheft->linked_document_id) {
            $pflichtenheft = SpecsDocument::find($lastenheft->linked_document_id);
        }

        $lastenheft->loadMissing('sections.requirements');

        $lhRequirements = $lastenheft->sections->flatMap(fn ($s) => $s->requirements);
        $totalLhReqs = $lhRequirements->count();

        // Get all traces from LH requirements
        $lhReqIds = $lhRequirements->pluck('id')->toArray();
        $traces = SpecsTrace::whereIn('source_requirement_id', $lhReqIds)->get();
        $coveredLhReqIds = $traces->pluck('source_requirement_id')->unique()->toArray();

        $coveredCount = count($coveredLhReqIds);
        $uncoveredCount = $totalLhReqs - $coveredCount;
        $coveragePercent = $totalLhReqs > 0 ? round(($coveredCount / $totalLhReqs) * 100, 1) : 0;

        $uncoveredRequirements = $lhRequirements
            ->filter(fn ($req) => !in_array($req->id, $coveredLhReqIds))
            ->map(fn (SpecsRequirement $req) => [
                'id' => $req->id,
                'requirement_id' => $req->requirement_id,
                'title' => $req->title,
                'priority' => $req->priority,
            ])
            ->values()
            ->toArray();

        // Priority breakdown
        $byPriority = [];
        foreach (SpecsRequirement::PRIORITIES as $priority) {
            $total = $lhRequirements->where('priority', $priority)->count();
            $covered = $lhRequirements->where('priority', $priority)
                ->filter(fn ($req) => in_array($req->id, $coveredLhReqIds))
                ->count();

            if ($total > 0) {
                $byPriority[$priority] = [
                    'total' => $total,
                    'covered' => $covered,
                    'uncovered' => $total - $covered,
                    'coverage_percent' => round(($covered / $total) * 100, 1),
                ];
            }
        }

        return [
            'lastenheft_id' => $lastenheft->id,
            'lastenheft_name' => $lastenheft->name,
            'pflichtenheft_id' => $pflichtenheft?->id,
            'pflichtenheft_name' => $pflichtenheft?->name,
            'total_requirements' => $totalLhReqs,
            'covered' => $coveredCount,
            'uncovered' => $uncoveredCount,
            'coverage_percent' => $coveragePercent,
            'by_priority' => $byPriority,
            'uncovered_requirements' => $uncoveredRequirements,
        ];
    }
}
