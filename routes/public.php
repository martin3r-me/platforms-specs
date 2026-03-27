<?php

use Platform\Specs\Livewire\Document\PublicShow;

Route::get('/public/{token}', PublicShow::class)
    ->name('specs.public.show');
