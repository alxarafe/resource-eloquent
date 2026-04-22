# alxarafe/resource-eloquent

**Eloquent ORM adapter for [alxarafe/resource-controller](https://github.com/alxarafe/resource-controller).**

Implements `RepositoryContract`, `QueryContract`, `TransactionContract`, and `RelationContract` using Laravel's Illuminate Database (Eloquent).

## Installation

```bash
composer require alxarafe/resource-eloquent
```

This will automatically pull in `alxarafe/resource-controller` and `illuminate/database`.

## Usage

```php
use Alxarafe\ResourceEloquent\EloquentRepository;
use Alxarafe\ResourceEloquent\EloquentTransaction;
use Alxarafe\ResourceEloquent\EloquentRelation;

// In your controller:
protected function getRepository(string $tabId = 'default'): RepositoryContract
{
    return new EloquentRepository(Product::class, ['category']);
}

protected function getTransaction(): TransactionContract
{
    return new EloquentTransaction();
}

protected function getRelationHandler(): ?RelationContract
{
    return new EloquentRelation(Product::class);
}
```

## Classes

| Class | Contract | Purpose |
|---|---|---|
| `EloquentRepository` | `RepositoryContract` | CRUD + schema introspection |
| `EloquentQuery` | `QueryContract` | Fluent query building |
| `EloquentTransaction` | `TransactionContract` | DB transactions |
| `EloquentRelation` | `RelationContract` | HasMany relation sync |

## License

GPL-3.0-or-later
