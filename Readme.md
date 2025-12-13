# Laravel Searchable Documentation

## Introduction

### Overview

Laravel Searchable is a powerful and flexible package that simplifies database searching in Laravel applications. It provides an elegant, fluent interface for building complex search queries across model attributes, relationships, and date ranges without writing verbose query builder code.

The package extends Laravel's Eloquent models with a convenient `search()` method that handles common search patterns like partial matching, relationship searching, concatenated field searches, and date filtering.

### Why Use Laravel Searchable?

**Clean and Readable Code**
Transform complex search queries into simple, declarative syntax. Instead of writing multiple `where` and `orWhere` clauses, define your searchable fields in a straightforward array.

**Relationship Search Support**
Seamlessly search across related models without manually writing `whereHas` queries. The package handles nested relationships automatically.

**Flexible Search Patterns**
Support multiple search patterns out of the box:
- Exact matches
- Partial matches (LIKE queries)
- Concatenated field searches (e.g., first name + last name)
- Date and date range filtering
- Multiple search conditions

**Chainable and Composable**
Chain multiple search methods together to build complex queries progressively. Combine text searches, status filters, and date ranges in any order.

**Zero Configuration**
Get started immediately with sensible defaults, or publish the configuration file for advanced customization.

---

## Installation

Install the package via Composer:

```bash
composer require nahid-ferdous/laravel-searchable
```

---

## Configuration

### Publishing the Configuration File

To customize the package behavior, publish the configuration file:

```bash
php artisan vendor:publish --provider="NahidFerdous\Searchable\SearchableServiceProvider"
```

This will create a `config/searchable.php` file where you can adjust package settings.

---

## Setup

### Adding the Trait to Your Model

To make a model searchable, import and use the `Searchable` trait:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use NahidFerdous\Searchable\Searchable;

class User extends Authenticatable
{
    use Searchable;
    
    // Define your relationships as usual
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

That's it! Your model is now ready to use the searchable features.

---

## Basic Usage

### Simple Field Search

Search across one or more fields in your model:

```php
$searchQuery = request('search_query');

$users = User::search($searchQuery, ['%name', '%email', 'phone'])->get();
```

**What this does:**
- Searches for `name` with partial matching (LIKE `%searchQuery%`)
- Searches for `email` with partial matching (LIKE `%searchQuery%`)
- Searches for exact `phone` match

**Generated SQL equivalent:**
```php
User::where('name', 'like', '%' . $searchQuery . '%')
    ->orWhere('email', 'like', '%' . $searchQuery . '%')
    ->orWhere('phone', $searchQuery)
    ->get();
```

### Understanding Search Operators

The package uses prefix operators to define search behavior:

- **`%field`** - Partial match (LIKE `%value%`)
- **`field`** - Exact match (WHERE `field` = `value`)

---

## Advanced Usage

### Searching Across Relationships

Search through related models seamlessly:

```php
$searchQuery = request('search_query'); // e.g., 'bangladesh'

$users = User::search($searchQuery, [
    '%name',
    '%email',
    '%phone',
    'country|%name',              // Search in related country's name
    'country.city|%name'          // Search in nested relationship
])->get();
```

**Relationship syntax:**
- `relationship|%field` - Search a related model's field
- `relationship.nested|%field` - Search through nested relationships

**Generated SQL equivalent:**
```php
User::where('name', 'like', '%' . $searchQuery . '%')
    ->orWhere('email', 'like', '%' . $searchQuery . '%')
    ->orWhere('phone', 'like', '%' . $searchQuery . '%')
    ->orWhereHas('country', function ($query) use ($searchQuery) {
        $query->where('name', 'like', '%' . $searchQuery . '%');
    })
    ->orWhereHas('country.city', function ($query) use ($searchQuery) {
        $query->where('name', 'like', '%' . $searchQuery . '%');
    })
    ->get();
```

### Concatenated Field Search

Search across multiple fields combined together (useful for full names, addresses, etc.):

```php
$searchQuery = request('search_query');

$users = User::search($searchQuery, [
    '%first_name',
    '%last_name',
    '%first_name+last_name',      // Search full name
])->get();
```

**Concatenation syntax:**
- `%field1+field2` - Combines fields with a space separator

**Generated SQL equivalent:**
```php
User::where('first_name', 'like', '%' . $searchQuery . '%')
    ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
    ->orWhere(DB::raw("concat(first_name, ' ', last_name)"), 'LIKE', '%' . $searchQuery . '%')
    ->get();
```

### Chaining Multiple Search Conditions

Combine multiple search criteria by chaining `search()` methods:

```php
$searchQuery = request('search_query');
$status = request('status');

$users = User::search($searchQuery, [
        '%first_name',
        '%last_name',
        '%first_name+last_name',
    ])
    ->search($status, ['status'])      // Add additional condition
    ->get();
```

**Generated SQL equivalent:**
```php
User::where('first_name', 'like', '%' . $searchQuery . '%')
    ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
    ->orWhere(DB::raw("concat(first_name, ' ', last_name)"), 'LIKE', '%' . $searchQuery . '%')
    ->where('status', $status)
    ->get();
```

---

## Date Searching

### Single Date Search

Filter records by a specific date with comparison operators:

```php
$status = request('status');
$date = request('date'); // e.g., '2020-01-01'

$users = User::search($status, ['status'])
    ->searchDate($date, ['created_at'], '>')
    ->get();
```

**Supported operators:**
- `>` - Greater than
- `<` - Less than
- `=` - Equal to
- `>=` - Greater than or equal to
- `<=` - Less than or equal to

**Generated SQL equivalent:**
```php
User::where('status', $status)
    ->whereDate('created_at', '>', $date)
    ->get();
```

### Date Range Search

Filter records within a date range:

```php
$status = request('status');
$dateRange = request('date_range'); // e.g., '2020-01-01 - 2020-01-31'

$users = User::search($status, ['status'])
    ->searchDate($dateRange, ['created_at'], '><')
    ->get();
```

**Date range format:**
- Must be separated by ` - ` (space, hyphen, space)
- Format: `YYYY-MM-DD - YYYY-MM-DD`

**Operator for ranges:**
- `><` - Between dates (inclusive)

**Generated SQL equivalent:**
```php
$startDate = explode(' - ', $dateRange)[0];
$endDate = explode(' - ', $dateRange)[1];
$start = Carbon::parse($startDate);
$end = Carbon::parse($endDate);

User::where('status', $status)
    ->whereBetween('created_at', [$start, $end])
    ->get();
```

### Date Search with Relationships

*Coming Soon*

This feature will allow you to filter by dates in related models.

### Date Range Search with Relationships

*Coming Soon*

This feature will allow you to filter by date ranges in related models.

---

## Complete Example

Here's a real-world example combining multiple search features:

```php
public function index(Request $request)
{
    $users = User::query()
        // Text search across multiple fields
        ->search($request->search_query, [
            '%first_name',
            '%last_name',
            '%first_name+last_name',
            '%email',
            'country|%name',
        ])
        // Filter by status
        ->search($request->status, ['status'])
        // Filter by role
        ->search($request->role, ['role'])
        // Date range filter
        ->searchDate($request->date_range, ['created_at'], '><')
        // Pagination
        ->paginate(20);
    
    return view('users.index', compact('users'));
}
```

**Example URL:**
```
http://example.com/users?search_query=john&status=1&role=admin&date_range=2020-01-01 - 2020-01-31
```

---

## Tips and Best Practices

### Performance Considerations

- Add database indexes to frequently searched columns
- Use exact matches when possible to avoid LIKE queries
- Consider using Laravel Scout for full-text search on large datasets
- Limit the number of searchable relationships to avoid N+1 queries

### Handling Empty Search Values

The package automatically handles empty or null search values gracefully - they simply won't add any conditions to your query.

### Combining with Other Query Methods

Since `search()` returns a query builder instance, you can chain any standard Eloquent methods:

```php
$users = User::search($searchQuery, ['%name'])
    ->where('verified', true)
    ->orderBy('created_at', 'desc')
    ->with('country')
    ->paginate(15);
```

---

## API Reference

### `search($value, array $fields)`

Adds search conditions to the query.

**Parameters:**
- `$value` (mixed) - The search term or value
- `$fields` (array) - Array of searchable fields with optional operators

**Returns:** Query builder instance for method chaining

### `searchDate($value, array $fields, string $operator = '=')`

Adds date-based search conditions to the query.

**Parameters:**
- `$value` (string) - Date value or date range
- `$fields` (array) - Array of date fields to search
- `$operator` (string) - Comparison operator (`>`, `<`, `=`, `>=`, `<=`, `><`)

**Returns:** Query builder instance for method chaining

---

## Troubleshooting

### Common Issues

**Search not working across relationships**
- Ensure the relationship is properly defined in your model
- Verify the relationship name matches exactly (case-sensitive)

**Date search returning unexpected results**
- Check date format is `YYYY-MM-DD`
- For date ranges, ensure format is `YYYY-MM-DD - YYYY-MM-DD` with spaces

**Performance issues with large datasets**
- Add database indexes to searched columns
- Limit the number of searchable relationships
- Consider implementing pagination

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## License

This package is open-sourced software licensed under the MIT license.

---

## Support

If you encounter any issues or have questions, please open an issue on the GitHub repository.
