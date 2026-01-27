<?php

declare(strict_types=1);

namespace TentaPress\Export;

use Illuminate\Support\ServiceProvider;
use TentaPress\Export\Services\Exporter;

final class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Exporter::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tentapress-export');
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
    }
}
