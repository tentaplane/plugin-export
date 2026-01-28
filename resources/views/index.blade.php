@extends('tentapress-admin::layouts.shell')

@section('title', 'Export')

@section('content')
    <div class="tp-page-header">
        <div>
            <h1 class="tp-page-title">Export</h1>
            <p class="tp-description">
                Export content and configuration to a zip bundle for another TentaPress installation.
            </p>
        </div>
    </div>

    <div class="tp-metabox">
        <div class="tp-metabox__title">Export bundle</div>
        <div class="tp-metabox__body space-y-5">
            <form method="POST" action="{{ route('tp.export.run') }}" class="space-y-4">
                @csrf

                <div class="tp-panel space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_settings" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include settings</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports rows from
                                <code class="tp-code">tp_settings</code>
                                .
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_posts" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include posts</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports rows from
                                <code class="tp-code">tp_posts</code>
                                .
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_media" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include media</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports rows from
                                <code class="tp-code">tp_media</code>
                                (metadata only).
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_theme" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include theme metadata</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports active theme ID (if available) and discovered layouts (if available).
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_plugins" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include enabled plugins list</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports enabled plugin IDs from the plugin cache (when available).
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_seo" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include SEO data</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Exports
                                <code class="tp-code">tp_seo_pages</code>
                                and
                                <code class="tp-code">tp_seo_posts</code>
                                if present. Safe to leave on.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="tp-button-primary">Generate export zip</button>
                </div>

                <div class="tp-muted text-xs">
                    The download will begin immediately. No export history is stored in the database.
                </div>
            </form>

            <div class="tp-divider"></div>

            <div class="tp-muted text-xs">
                Files included:
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><code class="tp-code">manifest.json</code></li>
                    <li><code class="tp-code">pages.json</code></li>
                    <li>
                        <code class="tp-code">posts.json</code>
                        (optional)
                    </li>
                    <li>
                        <code class="tp-code">media.json</code>
                        (optional)
                    </li>
                    <li>
                        <code class="tp-code">settings.json</code>
                        (optional)
                    </li>
                    <li>
                        <code class="tp-code">theme.json</code>
                        (optional)
                    </li>
                    <li>
                        <code class="tp-code">plugins.json</code>
                        (optional)
                    </li>
                    <li>
                        <code class="tp-code">seo.json</code>
                        (optional)
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
