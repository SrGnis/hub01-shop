<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Trait ExcludeScope
 *
 * This trait provides a scope to exclude specific columns from the query.
 *
 * Originally created by: https://stackoverflow.com/a/56425794
 */
trait ExcludeScope
{
    /**
     * Scope a query to exclude specific columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array $columns
     */
    #[Scope]
    protected function exclude(Builder $query, array $columns): void
    {
        if ($columns !== []) {
            $query->select(array_diff($this->getTableColumns(), \Illuminate\Support\Arr::flatten($columns)));
        }
    }

    /**
     * Shows All the columns of the Corresponding Table of Model
     *
     * If You need to get all the Columns of the Model Table.
     * Useful while including the columns in search
     *
     * @return array
     */
    public function getTableColumns(): array
    {
        return Cache::rememberForever('MigrMod:'.filemtime(database_path('migrations')).':'.$this->getTable(), function () {
            return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
        });
    }
}
