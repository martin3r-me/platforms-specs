<?php

namespace Platform\Specs;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SpecsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Step 1: Load config
        $this->mergeConfigFrom(__DIR__ . '/../config/specs.php', 'specs');

        // Step 2: Register module
        if (
            config()->has('specs.routing') &&
            config()->has('specs.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key' => 'specs',
                'title' => 'Specs',
                'routing' => config('specs.routing'),
                'guard' => config('specs.guard'),
                'navigation' => config('specs.navigation'),
                'sidebar' => config('specs.sidebar'),
            ]);
        }

        // Step 3: Routes (if module registered)
        if (PlatformCore::getModule('specs')) {
            ModuleRouter::group('specs', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });

            // Public routes (without auth)
            ModuleRouter::group('specs', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/public.php');
            }, requireAuth: false);
        }

        // Step 4: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 5: Publish config
        $this->publishes([
            __DIR__ . '/../config/specs.php' => config_path('specs.php'),
        ], 'config');

        // Step 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'specs');
        $this->registerLivewireComponents();

        // Step 7: Tools
        $this->registerTools();
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Specs\Tools\SpecsOverviewTool());

            // Document CRUD
            $registry->register(new \Platform\Specs\Tools\ListDocumentsTool());
            $registry->register(new \Platform\Specs\Tools\GetDocumentTool());
            $registry->register(new \Platform\Specs\Tools\CreateDocumentTool());
            $registry->register(new \Platform\Specs\Tools\UpdateDocumentTool());
            $registry->register(new \Platform\Specs\Tools\DeleteDocumentTool());

            // Section CRUD
            $registry->register(new \Platform\Specs\Tools\ListSectionsTool());
            $registry->register(new \Platform\Specs\Tools\CreateSectionTool());
            $registry->register(new \Platform\Specs\Tools\UpdateSectionTool());
            $registry->register(new \Platform\Specs\Tools\DeleteSectionTool());

            // Requirement CRUD
            $registry->register(new \Platform\Specs\Tools\ListRequirementsTool());
            $registry->register(new \Platform\Specs\Tools\CreateRequirementTool());
            $registry->register(new \Platform\Specs\Tools\UpdateRequirementTool());
            $registry->register(new \Platform\Specs\Tools\DeleteRequirementTool());
            $registry->register(new \Platform\Specs\Tools\BulkCreateRequirementsTool());
            $registry->register(new \Platform\Specs\Tools\ReorderRequirementsTool());

            // Acceptance Criteria CRUD
            $registry->register(new \Platform\Specs\Tools\ListAcceptanceCriteriaTool());
            $registry->register(new \Platform\Specs\Tools\CreateAcceptanceCriterionTool());
            $registry->register(new \Platform\Specs\Tools\UpdateAcceptanceCriterionTool());
            $registry->register(new \Platform\Specs\Tools\DeleteAcceptanceCriterionTool());

            // Traces
            $registry->register(new \Platform\Specs\Tools\ListTracesTool());
            $registry->register(new \Platform\Specs\Tools\CreateTraceTool());
            $registry->register(new \Platform\Specs\Tools\DeleteTraceTool());
            $registry->register(new \Platform\Specs\Tools\CoverageAnalysisTool());

            // Snapshots
            $registry->register(new \Platform\Specs\Tools\CreateSnapshotTool());
            $registry->register(new \Platform\Specs\Tools\ListSnapshotsTool());
            $registry->register(new \Platform\Specs\Tools\GetSnapshotTool());
            $registry->register(new \Platform\Specs\Tools\CompareSnapshotsTool());

            // Comments
            $registry->register(new \Platform\Specs\Tools\ListCommentsTool());

            // Utilities
            $registry->register(new \Platform\Specs\Tools\ExportDocumentTool());
        } catch (\Throwable $e) {
            \Log::warning('Specs: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Specs\\Livewire';
        $prefix = 'specs';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
