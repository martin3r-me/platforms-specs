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
];
