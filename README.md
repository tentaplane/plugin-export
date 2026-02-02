# Export

Content export functionality for TentaPress.

## Plugin Details

| Field | Value |
|-------|-------|
| ID | `tentapress/export` |
| Version | 0.1.2 |
| Provider | `TentaPress\Export\ExportServiceProvider` |

## Features

- Export site content to JSON format
- Includes pages, posts, media references, settings

## Dependencies

None.

## Admin Menu

| Label | Route | Capability | Parent |
|-------|-------|------------|--------|
| Export | `tp.export.index` | `export_site` | Settings |

## Development

```bash
php artisan tp:plugins sync
php artisan tp:plugins enable tentapress/export
```

**Note:** This is an optional plugin. Not enabled by default.
