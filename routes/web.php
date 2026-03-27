<?php

use Platform\Specs\Livewire\Dashboard;
use Platform\Specs\Livewire\Document\Index;
use Platform\Specs\Livewire\Document\Show;

Route::get('/', Dashboard::class)->name('specs.dashboard');
Route::get('/documents', Index::class)->name('specs.documents.index');
Route::get('/documents/{document}', Show::class)->name('specs.documents.show');
