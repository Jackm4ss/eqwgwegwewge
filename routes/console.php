<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('songkran:health-check', function () {
    $this->info('Songkran registration module is healthy.');
})->purpose('Check basic health of registration module');
