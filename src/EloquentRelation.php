<?php

declare(strict_types=1);

namespace Alxarafe\ResourceEloquent;

use Alxarafe\ResourceController\Contracts\RelationContract;

/**
 * EloquentRelation — RelationContract using Eloquent model relationships.
 *
 * Synchronizes child records (create/update/delete) for HasMany relations.
 */
class EloquentRelation implements RelationContract
{
    /** @var class-string<\Illuminate\Database\Eloquent\Model> */
    private string $parentModelClass;

    public function __construct(string $parentModelClass)
    {
        $this->parentModelClass = $parentModelClass;
    }

    public function syncRelation(string|int $parentId, string $relationName, array $rows, array $meta): void
    {
        $parent = (new $this->parentModelClass())->newQuery()->findOrFail($parentId);

        if (!method_exists($parent, $relationName)) {
            return;
        }

        $relation = $parent->$relationName();
        $related = $relation->getRelated();
        $foreignKey = $relation->getForeignKeyName();
        $relatedKeyName = $related->getKeyName();

        // Collect IDs to keep
        $keepIds = [];
        foreach ($rows as $row) {
            if (is_array($row) && !empty($row[$relatedKeyName])) {
                $keepIds[] = $row[$relatedKeyName];
            }
        }

        // Delete removed rows
        $deleteQuery = $related->where($foreignKey, $parentId);
        if (!empty($keepIds)) {
            $deleteQuery->whereNotIn($relatedKeyName, $keepIds);
        }
        $deleteQuery->delete();

        // Upsert remaining rows
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowId = $row[$relatedKeyName] ?? null;
            unset($row[$relatedKeyName]);
            $row[$foreignKey] = $parentId;

            $related->updateOrCreate(
                [$relatedKeyName => $rowId],
                $row
            );
        }
    }
}
