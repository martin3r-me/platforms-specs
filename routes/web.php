<?php

use Platform\Specs\Livewire\Dashboard;
use Platform\Specs\Livewire\Document\Index;
use Platform\Specs\Livewire\Document\Show;
use Platform\Specs\Livewire\Document\Requirements;
use Platform\Specs\Livewire\Document\Traces;
use Platform\Specs\Livewire\Document\Snapshots;

Route::get('/', Dashboard::class)->name('specs.dashboard');
Route::get('/documents', Index::class)->name('specs.documents.index');
Route::get('/documents/{document}', Show::class)->name('specs.documents.show');
Route::get('/documents/{document}/requirements', Requirements::class)->name('specs.documents.requirements');
Route::get('/documents/{document}/traces', Traces::class)->name('specs.documents.traces');
Route::get('/documents/{document}/snapshots', Snapshots::class)->name('specs.documents.snapshots');
