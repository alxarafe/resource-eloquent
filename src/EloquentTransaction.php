<?php

declare(strict_types=1);

namespace Alxarafe\ResourceEloquent;

use Alxarafe\ResourceController\Contracts\TransactionContract;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * EloquentTransaction — TransactionContract using Illuminate DB connection.
 */
class EloquentTransaction implements TransactionContract
{
    public function begin(): void
    {
        DB::connection()->beginTransaction();
    }

    public function commit(): void
    {
        DB::connection()->commit();
    }

    public function rollback(): void
    {
        DB::connection()->rollBack();
    }

    public function wrap(callable $callback): mixed
    {
        $this->begin();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
