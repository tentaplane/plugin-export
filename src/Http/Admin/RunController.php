<?php

declare(strict_types=1);

namespace TentaPress\Export\Http\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TentaPress\Export\Services\Exporter;

final class RunController
{
    public function __invoke(Request $request, Exporter $exporter): BinaryFileResponse
    {
        $data = $request->validate([
            'include_settings' => ['nullable', 'boolean'],
            'include_theme' => ['nullable', 'boolean'],
            'include_plugins' => ['nullable', 'boolean'],
            'include_seo' => ['nullable', 'boolean'],
            'include_posts' => ['nullable', 'boolean'],
            'include_media' => ['nullable', 'boolean'],
        ]);

        $options = [
            'include_settings' => (bool) ($data['include_settings'] ?? true),
            'include_theme' => (bool) ($data['include_theme'] ?? true),
            'include_plugins' => (bool) ($data['include_plugins'] ?? true),
            'include_seo' => (bool) ($data['include_seo'] ?? true),
            'include_posts' => (bool) ($data['include_posts'] ?? true),
            'include_media' => (bool) ($data['include_media'] ?? true),
        ];

        $result = $exporter->createExportZip($options);

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }
}
