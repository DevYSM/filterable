# YSM Filterable

The `YSM\Filterable` package provides a flexible and reusable way to filter Eloquent queries in Laravel applications. It
allows developers to apply dynamic query filters based on HTTP request parameters, with support for whitelisting,
blacklisting, aliases, and default values. This package is designed to keep filtering logic separate from controllers
and models, promoting clean code and adherence to the Single Responsibility Principle.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Basic Usage](#basic-usage)
    - [Advanced Use Cases](#advanced-use-cases)
        - [Using Aliases](#using-aliases)
        - [Auto-Applying Filters](#auto-applying-filters)
        - [Whitelisting and Blacklisting Filters](#whitelisting-and-blacklisting-filters)
        - [Setting Default Filter Values](#setting-default-filter-values)
        - [Debugging Applied Filters](#debugging-applied-filters)
- [Contributing](#contributing)
- [License](#license)

## Installation

1. **Install the Package via Composer**:
   Install the `YSM\Filterable` package using Composer:

   ```bash
   composer require ysm/filterable
   ```

2. **Publish Configuration (Optional)**:
   If the package includes a configuration file, publish it to customize settings:

   ```bash
   php artisan vendor:publish --provider="YSM\Filterable\FilterableServiceProvider"
   ```

   *Note*: If no service provider exists, you can skip this step as the package is ready to use after installation.

3. **Requirements**:
    - PHP 8.0 or higher
    - Laravel 8.x or higher

## Configuration

The `YSM\Filterable` package does not require additional configuration out of the box. It integrates seamlessly with
Laravel's Eloquent ORM and HTTP request handling. To use it, you need to:

1. Create a filter class that extends `YSM\Filterable\Filterable`.
2. Apply the `InteractWithFilterable` trait to your Eloquent models.

## Usage

### Basic Usage

The package provides an abstract `Filterable` class and a trait `InteractWithFilterable` to apply filters to Eloquent
queries.

#### Step 1: Add the Trait to Your Model

Add the `InteractWithFilterable` trait to your Eloquent model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use YSM\Filterable\Concerns\InteractWithFilterable;

class Post extends Model
{
    use InteractWithFilterable;

    protected $fillable = ['title', 'category', 'published', 'created_at'];
}
```

This adds a `filterable` scope to the `Post` model, allowing you to apply filters using a `Filterable` instance.

#### Step 2: Create a Filter Class

Create a filter class that extends `YSM\Filterable\Filterable`:

```php
<?php

namespace App\Http\Filters;

use YSM\Filterable\Filterable;
use Illuminate\Database\Eloquent\Builder;

class PostFilter extends Filterable
{
    protected array $allowedFilters = ['title', 'category', 'published'];

    public function title(string $value): void
    {
        $this->builder->where('title', 'like', "%{$value}%");
    }

    public function category(string $value): void
    {
        $this->builder->where('category', $value);
    }

    public function published(bool $value): void
    {
        $this->builder->where('published', $value);
    }
}
```

#### Step 3: Use in a Controller

Apply the filter in a controller:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Filters\PostFilter;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $filter = PostFilter::make();
        $posts = Post::filterable($filter)->get();
        return response()->json(['data' => $posts]);
    }
}
```

**Example Request**:

```bash
curl -X GET "http://your-app.test/posts?title=Test&category=news&published=1" \
     -H "Accept: application/json"
```

**Response**:

```json
{
    "data": [
        {
            "id": 1,
            "title": "Test Post",
            "category": "news",
            "published": true,
            "created_at": "2025-01-01T00:00:00.000000Z"
        }
    ]
}
```

### Advanced Use Cases

#### Using Aliases

Map request parameters to filter methods using the `aliases` method:

```php
<?php

namespace App\Http\Filters;

use YSM\Filterable\Filterable;
use Illuminate\Database\Eloquent\Builder;

class PostFilter extends Filterable
{
    protected array $allowedFilters = ['title', 'category', 'published'];
    protected array $aliases = [
        'cat' => 'category', // Maps 'cat' request param to 'category' filter
    ];

    public function title(string $value): void
    {
        $this->builder->where('title', 'like', "%{$value}%");
    }

    public function category(string $value): void
    {
        $this->builder->where('category', $value);
    }

    public function published(bool $value): void
    {
        $this->builder->where('published', $value);
    }
}
```

**Controller**:

```php
$filter = PostFilter::make()->aliases(['cat' => 'category']);
$posts = Post::filterable($filter)->get();
```

**Request**:

```bash
curl -X GET "http://your-app.test/posts?cat=news"
```

This applies the `category` filter using the `cat` request parameter.

#### Auto-Applying Filters

Automatically apply filters without requiring request parameters:

```php
<?php

namespace App\Http\Filters;

use YSM\Filterable\Filterable;
use Illuminate\Database\Eloquent\Builder;

class PostFilter extends Filterable
{
    protected array $autoApplyFilters = ['published'];

    public function published(bool $value = true): void
    {
        $this->builder->where('published', $value);
    }
}
```

**Controller**:

```php
$filter = PostFilter::make()->autoApply(['published']);
$posts = Post::filterable($filter)->get();
```

This ensures all queries return only published posts unless overridden.

#### Whitelisting and Blacklisting Filters

Restrict which filters can or cannot be applied:

```php
<?php

namespace App\Http\Filters;

use YSM\Filterable\Filterable;
use Illuminate\Database\Eloquent\Builder;

class PostFilter extends Filterable
{
    protected array $allowedFilters = ['title', 'category']; // Whitelist
    protected array $forbiddenFilters = ['created_at']; // Blacklist

    public function title(string $value): void
    {
        $this->builder->where('title', 'like', "%{$value}%");
    }

    public function category(string $value): void
    {
        $this->builder->where('category', $value);
    }

    public function created_at(string $value): void
    {
        $this->builder->whereDate('created_at', $value);
    }
}
```

**Controller**:

```php
$filter = PostFilter::make()->only(['title', 'category'])->except(['created_at']);
$posts = Post::filterable($filter)->get();
```

**Request**:

```bash
curl -X GET "http://your-app.test/posts?title=Test&created_at=2025-01-01"
```

The `created_at` filter will be ignored due to the blacklist.

#### Setting Default Filter Values

Provide default values for filters:

```php
<?php

namespace App\Http\Filters;

use YSM\Filterable\Filterable;
use Illuminate\Database\Eloquent\Builder;

class PostFilter extends Filterable
{
    protected array $defaults = ['published' => true, 'category' => 'blog'];

    public function category(string $value): void
    {
        $this->builder->where('category', $value);
    }

    public function published(bool $value): void
    {
        $this->builder->where('published', $value);
    }
}
```

**Controller**:

```php
$filter = PostFilter::make()->defaults(['published' => true, 'category' => 'blog']);
$posts = Post::filterable($filter)->get();
```

**Request**:

```bash
curl -X GET "http://your-app.test/posts"
```

This returns only published blog posts if no parameters are provided.

#### Debugging Applied Filters

Retrieve applied filters for debugging:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Filters\PostFilter;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $filter = PostFilter::make()->only(['title', 'category']);
        $posts = Post::filterable($filter)->get();
        return response()->json([
            'data' => $posts,
            'applied_filters' => $filter->getAppliedFilters(),
            'configured_filters' => $filter->getConfiguredFilters(),
        ]);
    }
}
```

**Response**:

```json
{
    "data": [
        {
            "id": 1,
            "title": "Test Post",
            "category": "news",
            "published": true,
            "created_at": "2025-01-01T00:00:00.000000Z"
        }
    ],
    "applied_filters": {
        "title": "Test",
        "category": "news"
    },
    "configured_filters": {
        "autoApply": [],
        "aliases": [],
        "allowed": [
            "title",
            "category"
        ],
        "forbidden": [],
        "defaults": []
    }
}
```

### Contributing

Contributions are welcome! Please submit pull requests or issues to
the [GitHub repository](https://github.com/your-repo/ysm-filterable). Ensure your code follows PSR-12 standards.

### License

This package is open-sourced under the [MIT License](LICENSE).
