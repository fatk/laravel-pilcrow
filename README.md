# Wp Kit

## Installation

```bash
composer require fatk/pilcrow
```

## Usage

The Pilcrow library provides several helper classes to simplify working with WordPress posts, caching, paths, and post types. Highly experimental, use at your own risks.

Here's how to use each class:

### Post Class

The `Post` class manages WordPress post operations with caching and path-based lookups.

```php
use Fatk\Pilcrow\Helpers\Post;

// Create a new Post instance
$post = new Post('/sample-parent/sample-page', 'page');

// Find an existing post
$existingPost = $post->find();

// Find the parent post from its path
$parentPost = $post->findParent();

// Set post data
$post->set(collect([
    'post_title' => 'Sample Page',
    'post_content' => 'This is a sample page content.',
    'post_author' => 1
]));

// Update or create the post if it doesn't exist
$saveStatus = $post->save();
```

### Path Class

The `Path` class handles operations on URL paths, including parsing and segmenting.

```php
use Fatk\Pilcrow\Helpers\Path;

// Create a Path instance
$path = new Path('/post-type-prefix/sample-page/child-page');

// Get the original path
$originalPath = $path->get();

// Get path segments as a Collection
$segments = $path->segment();

// Remove post type prefix
$pathWithoutPrefix = $path->removePostTypePrefix('post-type');
```

### PostType Class

The `PostType` class helps retrieve information about post types, such as their rewrite slugs.

```php
use Fatk\Pilcrow\Helpers\PostType;

$postType = new PostType();

// Get the rewrite slug (prefix) for a post type
$prefix = $postType->getPrefix('post-type');
```

These classes work together to provide a robust toolkit for managing WordPress content. The `Post` class uses `Path` for handling URLs, `Cache` for improved performance, and `PostType` for working with different content types.

Remember to import the necessary classes and ensure that your WordPress environment is properly set up before using these helpers.

## License

CC BY-NC 4.0. Please see the [license file](LICENSE) for more information.

