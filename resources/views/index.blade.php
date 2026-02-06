@extends('tentapress-admin::layouts.shell')

@section('title', 'Export')

@section('content')
    <div class="tp-page-header">
        <div>
            <h1 class="tp-page-title">Export</h1>
            <p class="tp-description">
                Create an export file you can import into another TentaPress site.
            </p>
        </div>
    </div>

    <div class="tp-metabox">
        <div class="tp-metabox__title">Export file</div>
        <div class="tp-metabox__body space-y-5">
            <form method="POST" action="{{ route('tp.export.run') }}" class="space-y-4">
                @csrf

                <div class="tp-panel space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_settings" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include settings</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include your site settings.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_posts" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include posts</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include your posts.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_media" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include media</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include media details. Files are not included.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_theme" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include theme details</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include the active theme and any available layouts.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_plugins" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include enabled plugins</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include a list of plugins currently enabled on this site.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="tp-checkbox" name="include_seo" value="1" checked />
                        <span class="text-sm">
                            <span class="font-semibold">Include SEO data</span>
                            <span class="tp-muted mt-1 block text-xs">
                                Include SEO titles, descriptions, and social sharing settings when available.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="tp-button-primary">Create export file</button>
                </div>

                <div class="tp-muted text-xs">
                    Your download starts right away. Export history is not saved.
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
