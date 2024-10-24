# Pilcrow

## Installation

```bash
composer require fatk/pilcrow
```

## Usage

The `pilcrow:import` command allows you to import various types of content into WordPress from different sources.

### Basic Usage

```bash
wp acorn pilcrow:import {type} --source={source}
```

### Arguments

- `type`: The type of content to import
  - `post`: WordPress posts/pages
  - `user`: WordPress users
  - `attachment`: Media attachments

### Options

- `--source or -s`: Source adapter to use (Required)
  - `excel`: Import from Excel files (supports .xlsx, .xls, .csv)
  - `content`: Import from content files (supports .txt, .md, .json)
- `--path or -p`: Override the default source path
- `--file or -f` or `-f`: Filter files by name or pattern
- `--interactive` or `-i`: Select files interactively
- `--verbose or -v`: Output a detailed log of operations

### Default Paths

The package looks for import files in these default locations:

```php
// config/pilcrow.php
'import' => [
    'source' => [
        'path' => [
            'excel' => resource_path('data/import/excel'),
            'content' => resource_path('data/import/content'),
        ],
    ],
]
```

### Examples

Import posts from an Excel file:
```bash
wp acorn pilcrow:import post --source=excel
```

Import users from specific files matching a pattern:
```bash
wp acorn pilcrow:import user --source=excel --file="users*.xlsx"
```

Import attachments with interactive file selection:
```bash
wp acorn pilcrow:import attachment --source=content -i
```

Import posts from a custom directory:
```bash
wp acorn pilcrow:import post --source=content --path=/custom/path/to/files
```

### File Format Examples

#### Excel Import (posts)

Your Excel file should have these columns:

#### Excel Import Format

Your Excel file should follow this structure:

| Path            | Template      | Type     | Status  | Author  | Excerpt        | SEO Title      | SEO Description       | SEO Keyword        |
|----------------|---------------|----------|---------|---------|----------------|----------------|---------------------|-------------------|
| /about-us      | template-name | page     | publish | admin   | Page excerpt   | SEO page title | SEO page description | keyword1, keyword2 |
| /blog/post-1   |       | post     | draft   | editor  | Post excerpt   | SEO post title | SEO post description | keyword3, keyword4 |

Column descriptions:

- `Path`: URL path for the post/page/custom post type (with or without domain)
  - For pages: `/about-us`, `/contact`
  - For posts: `/blog/post-title`, `/news/article-name`

- `Template`: Page template to use
  - Use the template filename without extension
  - Leave empty for standard template

- `Type`: Content type
  - `page`: WordPress page
  - `post`: WordPress post
  - Can also use custom post types

- `Status`: Publication status
  - `publish`: Published content
  - `draft`: Saved as draft
  - `private`: Private content
  - `pending`: Pending review

- `Author`: WordPress username of the author
  - Must be an existing user
  - Defaults to current user if not found

- `Excerpt`: Short description of the content
  - Used in search results and summaries
  - Optional field

- `SEO Title`: Custom title for SEO purposes
  - Used in meta tags
  - Optional field

- `SEO Description`: Meta description for search engines
  - Should be concise and descriptive
  - Optional field

- `SEO Keyword`: Keywords for SEO
  - Comma-separated list
  - Optional field

Example import command:
```bash
wp acorn pilcrow:import post --source=excel --file="content-import.xlsx"
```

Notes:
- All columns are required in the Excel file but can be empty if not needed
- Path should not include the domain name
- Author must exist in WordPress before import
- Multiple keywords should be comma-separated

### Interactive Mode

When using the `-i` or `--interactive` option, you'll see a list of available files:
```
Select files to import:
❯ ◻ document.xlsx        123.45 KB    24/10/2024 23:40
  ◻ long-filename.csv    894.20 KB    24/10/2024 23:41
  ◻ test.json             12.67 KB    24/10/2024 23:42
```

Use:
- Space to select/deselect files
- Arrow keys to navigate
- Enter to confirm selection
- Ctrl+C to cancel

### Source Type Support

Each source adapter supports specific file types:

- Excel Adapter:
  - `.xlsx`: Excel 2007+ files
  - `.xls`: Legacy Excel files
  - `.csv`: Comma-separated values

### Error Handling

The command will:
1. Validate file types before processing
2. Skip unsupported files
3. Display clear error messages for invalid data
4. Report overall import success/failure

### Tips

- Use `--file` to filter specific files when dealing with large directories
- Use interactive mode (`-i`) to verify files before import
- Configure default paths in `config/pilcrow.php` for your common import locations
- Files are validated for correct format before import begins

### Extending

To add custom source adapters:

1. Create a new adapter implementing `SourceAdapterInterface`
2. Register it in `PilcrowServiceProvider`:
```php
$this->app->singleton('pilcrow.import.sources', function () {
    return [
        'custom' => CustomAdapter::class,
    ];
});
```

## License

CC BY-NC 4.0. Please see the [license file](LICENSE) for more information.

