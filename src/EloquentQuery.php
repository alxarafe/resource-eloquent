<?php

declare(strict_types=1);

namespace Alxarafe\ResourceEloquent;

use Alxarafe\ResourceController\Contracts\QueryContract;
use Alxarafe\ResourceController\Result\PaginatedResult;
use Illuminate\Database\Eloquent\Builder;

/**
 * EloquentQuery — QueryContract implementation wrapping Eloquent Builder.
 */
class EloquentQuery implements QueryContract
{
    private Builder $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function where(string $field, string $operator, mixed $value): static
    {
        $this->builder->where($field, $operator, $value);
        return $this;
    }

    public function whereNull(string $field): static
    {
        $this->builder->whereNull($field);
        return $this;
    }

    public function whereNotNull(string $field): static
    {
        $this->builder->whereNotNull($field);
        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        $this->builder->whereIn($field, $values);
        return $this;
    }

    public function whereNotIn(string $field, array $values): static
    {
        $this->builder->whereNotIn($field, $values);
        return $this;
    }

    public function search(array $fields, string $term): static
    {
        $this->builder->where(function ($q) use ($fields, $term) {
            foreach ($fields as $field) {
                $q->orWhereRaw("LOWER({$field}) LIKE LOWER(?)", ["%{$term}%"]);
            }
        });
        return $this;
    }

    public function with(array $relations): static
    {
        $this->builder->with($relations);
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): static
    {
        $this->builder->orderBy($field, $direction);
        return $this;
    }

    public function paginate(int $limit, int $offset = 0): PaginatedResult
    {
        $total = $this->builder->count();
        $items = $this->builder->limit($limit)->offset($offset)->get()
            ->map(fn($m) => $m->toArray())->all();
        return new PaginatedResult($items, $total, $limit, $offset);
    }

    public function count(): int
    {
        return $this->builder->count();
    }

    public function whereGroup(callable $callback): static
    {
        $this->builder->where(function (Builder $q) use ($callback) {
            $callback(new self($q));
        });
        return $this;
    }
}
