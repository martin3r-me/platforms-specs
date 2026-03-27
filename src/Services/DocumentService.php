<?php

namespace Platform\Specs\Services;

use Platform\Specs\Models\SpecsDocument;
use Platform\Specs\Models\SpecsDocumentSnapshot;

class DocumentService
{
    public function createDocument(array $data): SpecsDocument
    {
        return SpecsDocument::create($data);
    }

    public function createSnapshot(SpecsDocument $document, int $userId): SpecsDocumentSnapshot
    {
        $documentData = $document->toDocumentArray();

        $latestVersion = $document->snapshots()->max('version') ?? 0;

        return $document->snapshots()->create([
            'version' => $latestVersion + 1,
            'snapshot_data' => $documentData,
            'created_by_user_id' => $userId,
        ]);
    }

    public function compareSnapshots(SpecsDocumentSnapshot $snapshotA, SpecsDocumentSnapshot $snapshotB): array
    {
        $dataA = $snapshotA->snapshot_data;
        $dataB = $snapshotB->snapshot_data;

        $sectionsA = collect($dataA['sections'] ?? []);
        $sectionsB = collect($dataB['sections'] ?? []);

        $diff = [];

        // Compare sections
        $idsA = $sectionsA->pluck('uuid')->toArray();
        $idsB = $sectionsB->pluck('uuid')->toArray();

        $addedSections = $sectionsB->filter(fn ($s) => !in_array($s['uuid'], $idsA))->values()->toArray();
        $removedSections = $sectionsA->filter(fn ($s) => !in_array($s['uuid'], $idsB))->values()->toArray();

        // Compare requirements within matching sections
        $modifiedRequirements = [];
        $addedRequirements = [];
        $removedRequirements = [];

        foreach ($sectionsB as $sectionB) {
            $sectionA = $sectionsA->firstWhere('uuid', $sectionB['uuid']);
            if (!$sectionA) {
                continue;
            }

            $reqsA = collect($sectionA['requirements'] ?? []);
            $reqsB = collect($sectionB['requirements'] ?? []);

            $reqIdsA = $reqsA->pluck('uuid')->toArray();
            $reqIdsB = $reqsB->pluck('uuid')->toArray();

            foreach ($reqsB as $reqB) {
                if (!in_array($reqB['uuid'], $reqIdsA)) {
                    $addedRequirements[] = $reqB;
                    continue;
                }

                $reqA = $reqsA->firstWhere('uuid', $reqB['uuid']);
                if ($reqA && ($reqA['title'] !== $reqB['title'] || $reqA['content'] !== $reqB['content'] || $reqA['status'] !== $reqB['status'])) {
                    $modifiedRequirements[] = [
                        'uuid' => $reqB['uuid'],
                        'requirement_id' => $reqB['requirement_id'],
                        'before' => ['title' => $reqA['title'], 'content' => $reqA['content'], 'status' => $reqA['status']],
                        'after' => ['title' => $reqB['title'], 'content' => $reqB['content'], 'status' => $reqB['status']],
                    ];
                }
            }

            foreach ($reqsA as $reqA) {
                if (!in_array($reqA['uuid'], $reqIdsB)) {
                    $removedRequirements[] = $reqA;
                }
            }
        }

        $hasChanges = !empty($addedSections) || !empty($removedSections) || !empty($addedRequirements) || !empty($removedRequirements) || !empty($modifiedRequirements);

        return [
            'snapshot_a' => ['version' => $snapshotA->version, 'created_at' => $snapshotA->created_at?->toISOString()],
            'snapshot_b' => ['version' => $snapshotB->version, 'created_at' => $snapshotB->created_at?->toISOString()],
            'changes' => [
                'sections_added' => $addedSections,
                'sections_removed' => $removedSections,
                'requirements_added' => $addedRequirements,
                'requirements_removed' => $removedRequirements,
                'requirements_modified' => $modifiedRequirements,
            ],
            'has_changes' => $hasChanges,
        ];
    }

    public function exportDocument(SpecsDocument $document): array
    {
        $documentData = $document->toDocumentArray();

        $totalRequirements = 0;
        $totalSections = count($documentData['sections']);
        $statusCounts = [];
        $priorityCounts = [];
        $typeCounts = [];

        foreach ($documentData['sections'] as $section) {
            foreach ($section['requirements'] as $req) {
                $totalRequirements++;
                $statusCounts[$req['status']] = ($statusCounts[$req['status']] ?? 0) + 1;
                $priorityCounts[$req['priority']] = ($priorityCounts[$req['priority']] ?? 0) + 1;
                $typeCounts[$req['requirement_type']] = ($typeCounts[$req['requirement_type']] ?? 0) + 1;
            }
        }

        return [
            'document' => $documentData['document'],
            'sections' => $documentData['sections'],
            'summary' => [
                'total_sections' => $totalSections,
                'total_requirements' => $totalRequirements,
                'by_status' => $statusCounts,
                'by_priority' => $priorityCounts,
                'by_type' => $typeCounts,
            ],
        ];
    }
}
