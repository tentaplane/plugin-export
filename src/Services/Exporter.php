<?php

declare(strict_types=1);

namespace TentaPress\Export\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use TentaPress\Media\Models\TpMedia;
use TentaPress\Pages\Models\TpPage;
use TentaPress\Posts\Models\TpPost;
use TentaPress\System\Plugin\PluginManager;
use TentaPress\System\Support\JsonPayload;
use TentaPress\System\Support\Paths;
use TentaPress\System\Theme\ThemeManager;
use ZipArchive;

final class Exporter
{
    public function __construct(private readonly JsonPayload $jsonPayload)
    {
    }

    /**
     * @param array{
     *   include_settings?:bool,
     *   include_theme?:bool,
     *   include_plugins?:bool,
     *   include_seo?:bool,
     *   include_posts?:bool,
     *   include_media?:bool
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
        $includePosts = (bool) ($options['include_posts'] ?? true);
        $includeMedia = (bool) ($options['include_media'] ?? true);

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
                'posts' => $includePosts,
                'media' => $includeMedia,
                'settings' => $includeSettings,
                'theme' => $includeTheme,
                'plugins' => $includePlugins,
                'seo' => false,
            ],
        ];

        // Always export pages
        $pages = $this->exportPages();
        $zip->addFromString('pages.json', $this->jsonPayload->encode($pages));

        if ($includePosts) {
            $posts = $this->exportPosts();
            $zip->addFromString('posts.json', $this->jsonPayload->encode($posts));
        }

        if ($includeMedia) {
            $media = $this->exportMedia();
            $zip->addFromString('media.json', $this->jsonPayload->encode($media));
        }

        if ($includeSettings) {
            $settings = $this->exportSettings();
            $zip->addFromString('settings.json', $this->jsonPayload->encode($settings));
        }

        if ($includeTheme) {
            $theme = $this->exportTheme();
            $zip->addFromString('theme.json', $this->jsonPayload->encode($theme));
        }

        if ($includePlugins) {
            $plugins = $this->exportPlugins();
            $zip->addFromString('plugins.json', $this->jsonPayload->encode($plugins));
        }

        if ($includeSeo) {
            $seo = $this->exportSeo();
            if ($seo !== null) {
                $manifest['includes']['seo'] = true;
                $zip->addFromString('seo.json', $this->jsonPayload->encode($seo));
            }
        }

        $zip->addFromString('manifest.json', $this->jsonPayload->encode($manifest));

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
        if (! class_exists(TpPage::class)) {
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
     * @return array<string,mixed>
     */
    private function exportPosts(): array
    {
        if (! class_exists(TpPost::class)) {
            return [
                'error' => 'Posts plugin not installed.',
                'items' => [],
            ];
        }

        if (! Schema::hasTable('tp_posts')) {
            return [
                'error' => 'Posts table tp_posts not found.',
                'items' => [],
            ];
        }

        $postModel = TpPost::class;

        $hasStatus = Schema::hasColumn('tp_posts', 'status');
        $hasLayout = Schema::hasColumn('tp_posts', 'layout');
        $hasBlocks = Schema::hasColumn('tp_posts', 'blocks');
        $hasPublishedAt = Schema::hasColumn('tp_posts', 'published_at');

        $rows = $postModel::query()->orderBy('id')->get();

        $items = [];
        foreach ($rows as $p) {
            $item = [
                'id' => (int) $p->id,
                'title' => (string) ($p->title ?? ''),
                'slug' => (string) ($p->slug ?? ''),
                'created_at' => isset($p->created_at) ? (string) $p->created_at : null,
                'updated_at' => isset($p->updated_at) ? (string) $p->updated_at : null,
                'author_id' => isset($p->author_id) ? (int) $p->author_id : null,
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

            if ($hasPublishedAt) {
                $item['published_at'] = isset($p->published_at) ? (string) $p->published_at : null;
            }

            $items[] = $item;
        }

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function exportMedia(): array
    {
        if (! class_exists(TpMedia::class)) {
            return [
                'error' => 'Media plugin not installed.',
                'items' => [],
            ];
        }

        if (! Schema::hasTable('tp_media')) {
            return [
                'error' => 'Media table tp_media not found.',
                'items' => [],
            ];
        }

        $mediaModel = TpMedia::class;

        $rows = $mediaModel::query()->orderBy('id')->get();

        $items = [];
        foreach ($rows as $m) {
            $items[] = [
                'id' => (int) $m->id,
                'title' => isset($m->title) ? (string) $m->title : null,
                'alt_text' => isset($m->alt_text) ? (string) $m->alt_text : null,
                'caption' => isset($m->caption) ? (string) $m->caption : null,
                'disk' => (string) ($m->disk ?? 'public'),
                'path' => (string) ($m->path ?? ''),
                'original_name' => isset($m->original_name) ? (string) $m->original_name : null,
                'mime_type' => isset($m->mime_type) ? (string) $m->mime_type : null,
                'size' => isset($m->size) ? (int) $m->size : null,
                'width' => isset($m->width) ? (int) $m->width : null,
                'height' => isset($m->height) ? (int) $m->height : null,
                'created_by' => isset($m->created_by) ? (int) $m->created_by : null,
                'updated_by' => isset($m->updated_by) ? (int) $m->updated_by : null,
                'created_at' => isset($m->created_at) ? (string) $m->created_at : null,
                'updated_at' => isset($m->updated_at) ? (string) $m->updated_at : null,
            ];
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

                if (is_array($cache)) {
                    $enabled = $this->extractEnabledPluginIds($cache);
                    if ($enabled !== []) {
                        $out['enabled'] = $enabled;
                        return $out;
                    }
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
     * @return array<int,string>
     */
    private function extractEnabledPluginIds(array $cache): array
    {
        if (isset($cache['enabled']) && is_array($cache['enabled'])) {
            return array_values(array_filter(array_map(strval(...), $cache['enabled'])));
        }

        $plugins = null;

        if (isset($cache['plugins']) && is_array($cache['plugins'])) {
            $plugins = $cache['plugins'];
        } elseif (isset($cache['discovered']) && is_array($cache['discovered'])) {
            $plugins = $cache['discovered'];
        } elseif (isset($cache['items']) && is_array($cache['items'])) {
            $plugins = $cache['items'];
        } elseif ($this->looksLikePluginList($cache)) {
            $plugins = $cache;
        }

        if (!is_array($plugins) || $plugins === []) {
            return [];
        }

        $keys = array_keys($plugins);
        $allKeysAreStrings = $keys !== [] && count(array_filter($keys, 'is_string')) === count($keys);

        if ($allKeysAreStrings) {
            return array_values(array_map(strval(...), $keys));
        }

        $ids = [];
        foreach ($plugins as $plugin) {
            if (!is_array($plugin)) {
                continue;
            }

            $id = $plugin['id'] ?? null;
            if (!is_string($id) || $id === '') {
                continue;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    private function looksLikePluginList(array $cache): bool
    {
        if ($cache === []) {
            return false;
        }

        foreach ($cache as $item) {
            if (!is_array($item)) {
                return false;
            }

            if (!array_key_exists('id', $item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Export SEO per-page meta if the table exists.
     *
     * @return array<string,mixed>|null
     */
    private function exportSeo(): ?array
    {
        $hasPages = Schema::hasTable('tp_seo_pages');
        $hasPosts = Schema::hasTable('tp_seo_posts');

        if (! $hasPages && ! $hasPosts) {
            return null;
        }

        $pages = [];
        $posts = [];

        if ($hasPages) {
            $rows = DB::table('tp_seo_pages')->orderBy('page_id')->get();
            foreach ($rows as $r) {
                $pages[] = [
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
        }

        if ($hasPosts) {
            $rows = DB::table('tp_seo_posts')->orderBy('post_id')->get();
            foreach ($rows as $r) {
                $posts[] = [
                    'post_id' => (int) ($r->post_id ?? 0),
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
        }

        return [
            'pages' => [
                'count' => count($pages),
                'items' => $pages,
            ],
            'posts' => [
                'count' => count($posts),
                'items' => $posts,
            ],
        ];
    }

}
