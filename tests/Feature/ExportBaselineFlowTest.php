<?php

declare(strict_types=1);

use TentaPress\Export\ExportServiceProvider;
use TentaPress\Users\Models\TpUser;

function registerExportProvider(): void
{
    app()->register(ExportServiceProvider::class);
    resolve('router')->getRoutes()->refreshNameLookups();
    resolve('router')->getRoutes()->refreshActionLookups();
}

it('redirects guests from export admin routes to login', function (): void {
    registerExportProvider();

    $this->get('/admin/export')->assertRedirect('/admin/login');
    $this->post('/admin/export')->assertRedirect('/admin/login');
});

it('allows a super admin to view export index', function (): void {
    registerExportProvider();

    $admin = TpUser::query()->create([
        'name' => 'Export Admin',
        'email' => 'export-admin@example.test',
        'password' => 'secret',
        'is_super_admin' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin/export')
        ->assertOk()
        ->assertViewIs('tentapress-export::index');
});

it('allows a super admin to create and download an export archive', function (): void {
    registerExportProvider();

    $admin = TpUser::query()->create([
        'name' => 'Export Admin',
        'email' => 'export-download@example.test',
        'password' => 'secret',
        'is_super_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post('/admin/export', [
            'include_settings' => '1',
            'include_theme' => '1',
            'include_plugins' => '1',
            'include_seo' => '1',
            'include_posts' => '1',
            'include_media' => '1',
        ]);

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');

    $contentDisposition = (string) $response->headers->get('content-disposition');

    expect($contentDisposition)
        ->toContain('attachment;')
        ->toContain('tentapress-export-')
        ->toContain('.zip');
});
