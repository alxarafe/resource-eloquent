<?php

declare(strict_types=1);

namespace Alxarafe\ResourceEloquent;

use Alxarafe\ResourceController\Contracts\RepositoryContract;
use Alxarafe\ResourceController\Contracts\QueryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * EloquentRepository — RepositoryContract implementation using Eloquent ORM.
 */
class EloquentRepository implements RepositoryContract
{
    /** @var class-string<Model> */
    private string $modelClass;
    private array $with;

    public function __construct(string $modelClass, array $with = [])
    {
        $this->modelClass = $modelClass;
        $this->with = $with;
    }

    public function query(): QueryContract
    {
        $builder = (new $this->modelClass())->newQuery();
        if (!empty($this->with)) {
            $builder->with($this->with);
        }
        /** @psalm-suppress InvalidArgument */
        return new EloquentQuery($builder);
    }

    public function find(string|int $id): ?array
    {
        $query = (new $this->modelClass())->newQuery();
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        return $query->find($id)?->toArray();
    }

    public function newRecord(): array
    {
        return [];
    }

    public function save(string|int|null $id, array $data): array
    {
        $model = ($id !== null && $id !== 'new')
            ? (new $this->modelClass())->newQuery()->findOrFail($id)
            : new $this->modelClass();

        $pkName = $model->getKeyName();
        foreach ($data as $key => $value) {
            if (($key === $pkName || $key === 'created_at' || $key === 'updated_at') && empty($value)) {
                continue;
            }
            $model->$key = $value;
        }

        if (!$model->save()) {
            throw new \RuntimeException('Failed to save record');
        }
        return $model->toArray();
    }

    public function delete(string|int $id): bool
    {
        $model = (new $this->modelClass())->newQuery()->find($id);
        return $model ? (bool) $model->delete() : false;
    }

    public function getPrimaryKey(): string
    {
        return (new $this->modelClass())->getKeyName();
    }

    public function getFieldMetadata(): array
    {
        if (method_exists($this->modelClass, 'getFields')) {
            return $this->modelClass::getFields();
        }
        return $this->introspectSchema();
    }

    public function storageExists(): bool
    {
        try {
            return DB::schema()->hasTable((new $this->modelClass())->getTable());
        } catch (\Throwable) {
            return false;
        }
    }

    private function introspectSchema(): array
    {
        $instance = new $this->modelClass();
        $fullTable = $instance->getConnection()->getTablePrefix() . $instance->getTable();

        if (!DB::schema()->hasTable($instance->getTable())) {
            return [];
        }

        try {
            $columns = DB::connection()->select("SHOW COLUMNS FROM `{$fullTable}`");
        } catch (\Throwable) {
            return [];
        }

        $fields = [];
        foreach ($columns as $col) {
            $name = (string) $col->Field;
            $dbType = (string) $col->Type;
            $nullable = $col->Null === 'YES';

            $length = null;
            if (preg_match('/\((.*)\)/', $dbType, $m)) {
                $length = $m[1];
            }

            $fields[$name] = [
                'field' => $name,
                'label' => ucfirst(str_replace('_', ' ', $name)),
                'genericType' => self::mapType($dbType),
                'dbType' => $dbType,
                'required' => !$nullable && $col->Default === null && ($col->Key ?? '') !== 'PRI',
                'length' => is_numeric($length) ? (int) $length : $length,
                'nullable' => $nullable,
                'default' => $col->Default,
            ];
        }
        return $fields;
    }

    private static function mapType(string $t): string
    {
        $t = strtolower($t);
        return match (true) {
            str_contains($t, 'bool'), str_contains($t, 'tinyint') => 'boolean',
            str_contains($t, 'int') => 'integer',
            str_contains($t, 'decimal'), str_contains($t, 'float'), str_contains($t, 'double') => 'decimal',
            str_contains($t, 'datetime'), str_contains($t, 'timestamp') => 'datetime',
            str_contains($t, 'date') => 'date',
            str_contains($t, 'time') => 'time',
            str_contains($t, 'text'), str_contains($t, 'blob') => 'textarea',
            default => 'text',
        };
    }
}
