<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use TentaPress\Export\Services\Exporter;

it('matches the golden manifest and file list for minimal export options', function (): void {
    $expectedManifestPath = __DIR__.'/../Fixtures/minimal-export-manifest.json';
    $expectedFilesPath = __DIR__.'/../Fixtures/minimal-export-file-list.json';

    $expectedManifest = json_decode((string) file_get_contents($expectedManifestPath), true, 512, JSON_THROW_ON_ERROR);
    $expectedFiles = json_decode((string) file_get_contents($expectedFilesPath), true, 512, JSON_THROW_ON_ERROR);

    File::deleteDirectory(storage_path('app/tp-exports'));

    $result = resolve(Exporter::class)->createExportZip([
        'include_settings' => false,
        'include_theme' => false,
        'include_plugins' => false,
        'include_seo' => false,
        'include_posts' => false,
        'include_media' => false,
    ]);

    $zipPath = (string) ($result['path'] ?? '');

    expect($zipPath)->not->toBe('');
    expect(is_file($zipPath))->toBeTrue();

    $zip = new ZipArchive();
    $opened = $zip->open($zipPath);

    expect($opened)->toBeTrue();

    $fileNames = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (is_string($name) && $name !== '') {
            $fileNames[] = $name;
        }
    }

    sort($fileNames);
    sort($expectedFiles);

    expect($fileNames)->toBe($expectedFiles);

    $manifestRaw = $zip->getFromName('manifest.json');
    $zip->close();

    expect(is_string($manifestRaw))->toBeTrue();

    $manifest = json_decode((string) $manifestRaw, true, 512, JSON_THROW_ON_ERROR);

    unset($manifest['generated_at_utc']);

    expect($manifest)->toBe($expectedManifest);

    if (is_file($zipPath)) {
        unlink($zipPath);
    }
});
