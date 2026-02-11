<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use TentaPress\Export\ExportServiceProvider;
use TentaPress\Users\Models\TpUser;

function registerExportProviderForEdgeCases(): void
{
    app()->register(ExportServiceProvider::class);
    resolve('router')->getRoutes()->refreshNameLookups();
    resolve('router')->getRoutes()->refreshActionLookups();
}

it('denies export access to non-super-admin users', function (): void {
    registerExportProviderForEdgeCases();

    $user = TpUser::query()->create([
        'name' => 'Export Regular User',
        'email' => 'export-regular@example.test',
        'password' => 'secret',
        'is_super_admin' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin/export')
        ->assertForbidden();

    $this->actingAs($user)
        ->post('/admin/export')
        ->assertForbidden();
});

it('validates export options as booleans and does not create an export on failure', function (): void {
    registerExportProviderForEdgeCases();
    File::deleteDirectory(storage_path('app/tp-exports'));

    $admin = TpUser::query()->create([
        'name' => 'Export Admin',
        'email' => 'export-validation@example.test',
        'password' => 'secret',
        'is_super_admin' => true,
    ]);

    $this->actingAs($admin)
        ->from('/admin/export')
        ->post('/admin/export', [
            'include_settings' => 'yes',
            'include_theme' => 'no',
            'include_plugins' => 'invalid',
            'include_seo' => 'maybe',
            'include_posts' => 'abc',
            'include_media' => 'def',
        ])
        ->assertRedirect('/admin/export')
        ->assertSessionHasErrors([
            'include_settings',
            'include_theme',
            'include_plugins',
            'include_seo',
            'include_posts',
            'include_media',
        ]);

    $exportDir = storage_path('app/tp-exports');
    expect(is_dir($exportDir))->toBeFalse();
});
