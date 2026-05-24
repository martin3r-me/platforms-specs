<?php

return [
    'name' => 'Specs',
    'description' => 'Lasten- und Pflichtenhefte',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'specs',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'specs' => [
                'title' => 'Specs',
                'icon' => 'heroicon-o-document-text',
                'route' => 'specs.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'specs' => [
            'title' => 'Specs',
            'icon' => 'heroicon-o-document-text',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'specs.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'documents' => [
                    'title' => 'Dokumente',
                    'route' => 'specs.documents.index',
                    'icon' => 'heroicon-o-document-text',
                ],
            ],
        ],
    ],
    'billables' => [
        [
            'model' => \Platform\Specs\Models\SpecsDocument::class,
            'type' => 'per_item',
            'label' => 'Specs-Dokument',
            'description' => 'Jedes erstellte Specs-Dokument verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.005, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Specs\Models\SpecsRequirement::class,
            'type' => 'per_item',
            'label' => 'Anforderung',
            'description' => 'Jede erstellte Anforderung verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.0025, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];
