# Verclam Doctrine Filter Paginator

Attribute-based filtering, pagination, and ordering for Symfony + Doctrine.

Define your filtering logic directly on DTO properties using PHP 8 attributes — no manual query building needed.

## Requirements

- PHP >= 8.1
- Symfony >= 6.4
- Doctrine ORM >= 2.17

## Installation

```bash
composer require verclam/doctrine-filter-paginator
```

If you're using Symfony Flex, the bundle is registered automatically. Otherwise, add it to `config/bundles.php`:

```php
return [
    // ...
    Verclam\DoctrineFilterPaginator\VerclamDoctrineFilterPaginatorBundle::class => ['all' => true],
];
```

## Configuration

The bundle works out of the box with no configuration. Optional settings:

```yaml
# config/packages/verclam_doctrine_filter_paginator.yaml
verclam_doctrine_filter_paginator:
    timezone: 'Europe/Paris'       # Default timezone for date filtering (BETWEEN operator)
    total_records_key: 'totalRecords' # Key name for total count in paginated results
    results_key: 'results'            # Key name for results in paginated results
```

### Result Keys Priority

The result array keys can be configured at three levels (highest priority first):

1. **Controller** — per-endpoint override via setter:
    ```php
    $dto->setTotalRecordsKey('total');
    $dto->setResultsKey('data');
    $result = $manager->getResult($dto);
    // Returns: ['total' => 150, 'data' => Iterator]
    ```

2. **DTO class** — per-DTO override in the constructor:
    ```php
    class ProductPaginationDto extends AbstractFiltersDTO
    {
        public function __construct()
        {
            $this->setTotalRecordsKey('total');
            $this->setResultsKey('data');
        }
    }
    ```

3. **Bundle config** — project-wide default (see above)

## Usage

### 1. Create a Filter DTO

Extend `AbstractFiltersDTO` and define your filters using attributes:

```php
use Verclam\DoctrineFilterPaginator\Attributes\FilterBy;
use Verclam\DoctrineFilterPaginator\Attributes\LeftJoin;
use Verclam\DoctrineFilterPaginator\Attributes\OrderBy;
use Verclam\DoctrineFilterPaginator\DTO\AbstractFiltersDTO;

class ProductPaginationDto extends AbstractFiltersDTO
{
    public const CLASS_NAME = Product::class;
    public const ALIAS = 'p';

    #[FilterBy(property: 'name', operator: FilterBy::LIKE)]
    public ?string $name = null;

    #[FilterBy(property: 'status', operator: FilterBy::EQUAL)]
    public ?string $status = null;

    #[FilterBy(property: 'price', operator: FilterBy::BETWEEN, options: ['dataTypes' => FilterBy::DATA_TYPES_STRING])]
    public ?string $price = null;

    #[FilterBy(property: 'category', operator: FilterBy::IN)]
    #[LeftJoin('category')]
    public ?array $categoryIds = null;

    #[OrderBy(property: 'createdAt')]
    public string $orderByCreatedAt = 'DESC';
}
```

### 2. Use in a Controller

```php
use Verclam\DoctrineFilterPaginator\FilterPagerManager;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

#[Route('/api/products', methods: ['GET'])]
public function list(
    #[MapQueryString] ProductPaginationDto $dto,
    FilterPagerManager $manager,
): JsonResponse {
    $result = $manager->getResult($dto);

    // $result = [
    //     'totalRecords' => 150,
    //     'results'      => Iterator<Product>,
    // ]

    return $this->json($result, 200, [], ['groups' => ['product_basic']]);
}
```

## Attributes Reference

### `#[FilterBy]`

Defines a filter on an entity property. Repeatable.

```php
#[FilterBy(property: 'entityProperty', operator: FilterBy::OPERATOR, options: [...])]
```

**Operators:**

| Operator | Description |
|---|---|
| `FilterBy::EQUAL` | Exact match (`=`) |
| `FilterBy::NOT_EQUAL` | Not equal (`!=`) |
| `FilterBy::GREATER_THAN` | Greater than (`>`) |
| `FilterBy::GREATER_THAN_OR_EQUAL` | Greater than or equal (`>=`) |
| `FilterBy::LESS_THAN` | Less than (`<`) |
| `FilterBy::LESS_THAN_OR_EQUAL` | Less than or equal (`<=`) |
| `FilterBy::LIKE` | Partial match (`LIKE %value%`) |
| `FilterBy::IN` | Value in array (`IN (...)`) |
| `FilterBy::MEMBER_OF` | Doctrine `MEMBER OF` for collections |
| `FilterBy::IS_NULL` | Null check (`IS NULL`) |
| `FilterBy::BETWEEN` | Range filter (expects JSON: `[min, max]`) |
| `FilterBy::CUSTOM_CONDITION` | Raw DQL condition |

**Options:**

| Option | Type | Description |
|---|---|---|
| `negation` | `string` | Negate the condition (e.g., `FilterBy::NOT`) |
| `dataTypes` | `string` | Data type handling (`FilterBy::DATA_TYPES_DATE` for timezone conversion) |
| `joined` | `bool` | Skip root alias prefix (for joined properties) |
| `dqlOptions` | `CustomDQLValueOption` | `IGNORE_VALUE` or `VALUE_AS_PARAMETER` for custom conditions |
| `filterCondition` | `FilterCondition` | `FilterCondition::AND` (default) or `FilterCondition::OR` |

### `#[LeftJoin]`

Defines a LEFT JOIN on related entities. Repeatable.

```php
// Simple join
#[LeftJoin('relation')]

// Chained joins
#[LeftJoin(['relation', 'subRelation', 'deepRelation'])]

// Version join (joins on entity class with WITH clause)
#[LeftJoin('alias', withVersion: true, className: VersionEntity::class)]
```

### `#[OrderBy]`

Defines ordering. Repeatable.

```php
#[OrderBy(property: 'createdAt')]
public string $orderByCreatedAt = 'DESC'; // Value must be 'ASC' or 'DESC'

#[OrderBy(property: 'name', joined: true)] // For joined entity properties
public string $orderByName = 'ASC';
```

### `#[Pager]`

Applied automatically via `AbstractFiltersDTO` on the `page` property. Handles pagination using `page` and `rows` query parameters.

## Advanced Usage

### Multiple Filters on Same Property

Use repeatable attributes with `FilterCondition::OR`:

```php
use Verclam\DoctrineFilterPaginator\Enum\FilterCondition;

#[FilterBy(property: 'createdBy', operator: FilterBy::EQUAL, options: ['filterCondition' => FilterCondition::OR])]
#[FilterBy(property: 'sharedWith', operator: FilterBy::MEMBER_OF, options: ['filterCondition' => FilterCondition::OR])]
public ?int $userId = null;
```

### Custom DQL Conditions

```php
use Verclam\DoctrineFilterPaginator\Enum\CustomDQLValueOption;

// Ignore the DTO value, use raw DQL
#[FilterBy(
    property: 'custom_dql_expression',
    operator: FilterBy::CUSTOM_CONDITION,
    options: ['dqlOptions' => CustomDQLValueOption::IGNORE_VALUE],
)]
public ?string $customFilter = null;

// Use the DTO value as a named parameter
#[FilterBy(
    property: 'custom_dql_expression',
    operator: FilterBy::CUSTOM_CONDITION,
    options: ['dqlOptions' => CustomDQLValueOption::VALUE_AS_PARAMETER],
)]
public ?string $customFilter = null;
```

### Custom Select (Non-Paginated)

Override the SELECT clause for aggregate queries:

```php
$dto = new ProductPaginationDto();
$dto->customSelectDQL = 'COUNT(p.id)';

$result = $manager->getResult($dto); // Returns raw query result (no pagination)
```

### Get All Without Filtering

Pass a class name directly:

```php
$result = $manager->getResult(null, Product::class);
```

## Development

```bash
# Install dependencies
composer install

# Run PHPStan
composer phpstan

# Check coding standards
composer phpcs:check

# Fix coding standards
composer phpcs:format
```

## License

AGPL-3.0-or-later
