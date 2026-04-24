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
        if (method_exists($this->modelClass, 'validateData')) {
            $errors = $this->modelClass::validateData($data);
            if (!empty($errors)) {
                throw new \RuntimeException('Validation failed: ' . implode('; ', $errors));
            }
        }

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

            $limits = self::computeNumericLimits($dbType);
            $fields[$name] = array_merge($fields[$name], $limits);
        }
        return $fields;
    }

    private static function computeNumericLimits(string $dbType): array
    {
        $t = strtolower($dbType);
        $unsigned = str_contains($t, 'unsigned');

        $intRanges = [
            'tinyint'   => ['signed' => [-128, 127],           'unsigned' => [0, 255]],
            'smallint'  => ['signed' => [-32768, 32767],        'unsigned' => [0, 65535]],
            'mediumint' => ['signed' => [-8388608, 8388607],    'unsigned' => [0, 16777215]],
            'bigint'    => ['signed' => [PHP_INT_MIN, PHP_INT_MAX], 'unsigned' => [0, PHP_INT_MAX]],
            'int'       => ['signed' => [-2147483648, 2147483647], 'unsigned' => [0, 4294967295]],
        ];

        foreach ($intRanges as $type => $ranges) {
            if (str_contains($t, $type)) {
                $range = $unsigned ? $ranges['unsigned'] : $ranges['signed'];
                return ['min' => $range[0], 'max' => $range[1], 'step' => 1, 'unsigned' => $unsigned];
            }
        }

        if (preg_match('/(?:decimal|numeric)\((\d+),(\d+)\)/', $t, $m)) {
            $precision = (int) $m[1];
            $scale = (int) $m[2];
            $maxVal = (float) (str_repeat('9', $precision - $scale) . '.' . str_repeat('9', $scale));
            $minVal = $unsigned ? 0 : -$maxVal;
            $step = $scale > 0 ? (float) ('0.' . str_repeat('0', $scale - 1) . '1') : 1;
            return ['min' => $minVal, 'max' => $maxVal, 'step' => $step,
                    'precision' => $precision, 'scale' => $scale, 'unsigned' => $unsigned];
        }

        if (str_contains($t, 'float') || str_contains($t, 'double')) {
            return ['unsigned' => $unsigned];
        }

        return [];
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
