<?php

declare(strict_types=1);

namespace TentaPress\Export\Services;

use TentaPress\Pages\Models\TpPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use TentaPress\System\Plugin\PluginManager;
use TentaPress\System\Support\Paths;
use TentaPress\System\Theme\ThemeManager;
use ZipArchive;

final class Exporter
{
    /**
     * @param array{
     *   include_settings?:bool,
     *   include_theme?:bool,
     *   include_plugins?:bool,
     *   include_seo?:bool
     * } $options
     *
     * @return array{path:string, filename:string}
     */
    public function createExportZip(array $options = []): array
    {
        $includeSettings = (bool) ($options['include_settings'] ?? true);
        $includeTheme = (bool) ($options['include_theme'] ?? true);
        $includePlugins = (bool) ($options['include_plugins'] ?? true);
        $includeSeo = (bool) ($options['include_seo'] ?? true);

        $timestamp = gmdate('Ymd-His');
        $filename = "tentapress-export-{$timestamp}.zip";

        $dir = storage_path('app/tp-exports');
        File::ensureDirectoryExists($dir);

        $zipPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();

        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        throw_if($opened !== true, \RuntimeException::class, 'Unable to create export zip.');

        $manifest = [
            'schema_version' => 1,
            'generated_at_utc' => gmdate('c'),
            'app' => [
                'name' => 'TentaPress',
            ],
            'includes' => [
                'pages' => true,
                'settings' => $includeSettings,
                'theme' => $includeTheme,
                'plugins' => $includePlugins,
                'seo' => false,
            ],
        ];

        // Always export pages
        $pages = $this->exportPages();
        $zip->addFromString('pages.json', $this->json($pages));

        if ($includeSettings) {
            $settings = $this->exportSettings();
            $zip->addFromString('settings.json', $this->json($settings));
        }

        if ($includeTheme) {
            $theme = $this->exportTheme();
            $zip->addFromString('theme.json', $this->json($theme));
        }

        if ($includePlugins) {
            $plugins = $this->exportPlugins();
            $zip->addFromString('plugins.json', $this->json($plugins));
        }

        if ($includeSeo) {
            $seo = $this->exportSeo();
            if ($seo !== null) {
                $manifest['includes']['seo'] = true;
                $zip->addFromString('seo.json', $this->json($seo));
            }
        }

        $zip->addFromString('manifest.json', $this->json($manifest));

        $zip->close();

        return [
            'path' => $zipPath,
            'filename' => $filename,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function exportPages(): array
    {
        if (!class_exists(TpPage::class)) {
            return [
                'error' => 'Pages plugin not installed.',
                'items' => [],
            ];
        }

        $pageModel = TpPage::class;

        $hasStatus = Schema::hasColumn('tp_pages', 'status');
        $hasLayout = Schema::hasColumn('tp_pages', 'layout');
        $hasBlocks = Schema::hasColumn('tp_pages', 'blocks');

        $rows = $pageModel::query()->orderBy('id')->get();

        $items = [];
        foreach ($rows as $p) {
            $item = [
                'id' => (int) $p->id,
                'title' => (string) ($p->title ?? ''),
                'slug' => (string) ($p->slug ?? ''),
                'created_at' => isset($p->created_at) ? (string) $p->created_at : null,
                'updated_at' => isset($p->updated_at) ? (string) $p->updated_at : null,
            ];

            if ($hasStatus) {
                $item['status'] = (string) ($p->status ?? '');
            }

            if ($hasLayout) {
                $item['layout'] = (string) ($p->layout ?? '');
            }

            if ($hasBlocks) {
                $blocks = $p->blocks;
                $item['blocks'] = is_array($blocks) ? $blocks : [];
            }

            $items[] = $item;
        }

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * Exports all tp_settings rows (not just autoload), for portability.
     *
     * @return array<string,mixed>
     */
    private function exportSettings(): array
    {
        if (!Schema::hasTable('tp_settings')) {
            return [
                'error' => 'Settings table tp_settings not found.',
                'items' => [],
            ];
        }

        $rows = DB::table('tp_settings')->orderBy('key')->get(['key', 'value', 'autoload']);

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'key' => (string) ($r->key ?? ''),
                'value' => isset($r->value) ? (string) $r->value : null,
                'autoload' => (bool) ($r->autoload ?? true),
            ];
        }

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function exportTheme(): array
    {
        $out = [
            'active_theme_id' => null,
            'layouts' => [],
        ];

        if (!class_exists(ThemeManager::class)) {
            $out['error'] = 'ThemeManager not available.';
            return $out;
        }

        $manager = resolve(ThemeManager::class);

        // Try common accessors without assuming the exact API
        $activeId = null;

        foreach (['activeThemeId', 'getActiveThemeId', 'activeId', 'getActiveId'] as $method) {
            if (method_exists($manager, $method)) {
                $activeId = $manager->{$method}();
                break;
            }
        }

        if (is_string($activeId) && $activeId !== '') {
            $out['active_theme_id'] = $activeId;
        }

        // Try to export discovered layouts if ThemeManager provides them
        foreach (['layouts', 'getLayouts', 'discoverLayouts'] as $method) {
            if (method_exists($manager, $method)) {
                $layouts = $manager->{$method}();
                if (is_array($layouts)) {
                    $out['layouts'] = $layouts;
                }
                break;
            }
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    private function exportPlugins(): array
    {
        $out = [
            'enabled' => [],
            'cache_path' => null,
        ];

        // Best source: plugin cache file
        if (class_exists(Paths::class) && method_exists(Paths::class, 'pluginCachePath')) {
            $cachePath = (string) Paths::pluginCachePath();
            $out['cache_path'] = $cachePath;

            if (is_file($cachePath)) {
                $cache = require $cachePath;

                if (is_array($cache) && isset($cache['enabled']) && is_array($cache['enabled'])) {
                    $out['enabled'] = array_values(array_map(strval(...), $cache['enabled']));
                    return $out;
                }
            }
        }

        // Fallback: ask PluginManager if available
        if (class_exists(PluginManager::class)) {
            $pm = resolve(PluginManager::class);

            foreach (['enabledPluginIds', 'getEnabledPluginIds', 'enabled'] as $method) {
                if (method_exists($pm, $method)) {
                    $ids = $pm->{$method}();
                    if (is_array($ids)) {
                        $out['enabled'] = array_values(array_map(strval(...), $ids));
                    }
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Export SEO per-page meta if the table exists.
     *
     * @return array<string,mixed>|null
     */
    private function exportSeo(): ?array
    {
        if (!Schema::hasTable('tp_seo_pages')) {
            return null;
        }

        $rows = DB::table('tp_seo_pages')->orderBy('page_id')->get();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'page_id' => (int) ($r->page_id ?? 0),
                'title' => isset($r->title) ? (string) $r->title : null,
                'description' => isset($r->description) ? (string) $r->description : null,
                'canonical_url' => isset($r->canonical_url) ? (string) $r->canonical_url : null,
                'robots' => isset($r->robots) ? (string) $r->robots : null,
                'og_title' => isset($r->og_title) ? (string) $r->og_title : null,
                'og_description' => isset($r->og_description) ? (string) $r->og_description : null,
                'og_image' => isset($r->og_image) ? (string) $r->og_image : null,
                'twitter_title' => isset($r->twitter_title) ? (string) $r->twitter_title : null,
                'twitter_description' => isset($r->twitter_description) ? (string) $r->twitter_description : null,
                'twitter_image' => isset($r->twitter_image) ? (string) $r->twitter_image : null,
            ];
        }

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param mixed $data
     */
    private function json(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
