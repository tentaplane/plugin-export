<?php

declare(strict_types=1);

use TentaPress\Export\Http\Admin\IndexController;
use TentaPress\Export\Http\Admin\RunController;
use Illuminate\Support\Facades\Route;
use TentaPress\System\Support\AdminRoutes;

AdminRoutes::group(function (): void {
    Route::middleware('tp.can:export_site')->group(function (): void {
        Route::get('/export', IndexController::class)->name('export.index');
        Route::post('/export', RunController::class)->name('export.run');
    });
});
