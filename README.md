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

Here’s the updated `README.md` with the new Excel column structure:

---

### Excel Import (posts)

Your Excel file should contain the following columns:

### Excel Import Format

Your Excel file should follow this structure:

| Post Title     | Path           | Page Template  | Post Type | Post Status | Post Author | Post Excerpt   | Post Content   | Post Date | Post Category | Tags Input       | SEO Title      | SEO Description       | SEO Keyword        |
|----------------|----------------|----------------|-----------|-------------|-------------|----------------|----------------|-----------|---------------|------------------|----------------|-----------------------|--------------------|
| About Us       | /about-us      | template-name  | page      | publish     | admin       | Page excerpt   | Page content   | 2024-10-23 | Category 1 | tag1, tag2        | SEO page title | SEO page description  | keyword1, keyword2 |
| Blog Post 1    | /blog/post-1   |                | post      | draft       | editor      | Post excerpt   | Post content   | 2024-10-20 | Category 2 | tag3, tag4        | SEO post title | SEO post description  | keyword3, keyword4 |

---

### **Column Descriptions**

- **`Post Title`**: Title of the post or page.
  - Example: `About Us`, `Blog Post 1`

- **`Path`**: URL path for the post/page/custom post type (with or without domain).
  - Example: `/about-us`, `/blog/post-title`

- **`Page Template`**: Template to be used for the page.
  - Use the template filename without the extension.
  - Leave empty for the default template.
  - Example: `template-name`

- **`Post Type`**: The type of content to create.
  - `page`: WordPress page
  - `post`: WordPress post
  - You can also use custom post types.
  - Example: `page`, `post`, `product`

- **`Post Status`**: The status of the post or page.
  - `publish`: Published
  - `draft`: Saved as a draft
  - `private`: Private content
  - `pending`: Pending review

- **`Post Author`**: The WordPress username of the author.
  - Must be an existing user.
  - Defaults to the current user if the username is not found.
  - Example: `admin`, `editor`

- **`Post Excerpt`**: A short summary of the content.
  - Used in summaries and search results.
  - Optional field.

- **`Post Content`**: The main content of the post or page.

- **`Post Date`**: The publication date of the post or page.
  - Format: `YYYY-MM-DD`
  - Example: `2024-10-23`

- **`Post Category`**: The category for the post.
  - If multiple, use a comma-separated list.
  - Example: `Category 1, Category 2`

- **`Tags Input`**: Tags associated with the post.
  - Use a comma-separated list of tags.
  - Example: `tag1, tag2`

- **`SEO Title`**: Custom title for SEO purposes.
  - This will appear in the `<title>` tag of the page.

- **`SEO Description`**: A meta description for search engines.
  - Should be concise and descriptive.

- **`SEO Keyword`**: Keywords for SEO.
  - Use a comma-separated list.
  - Example: `keyword1, keyword2`


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

