<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @property string $id
 */
abstract class Model extends EloquentModel
{
    use HasUuids;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->id ??= $model->newUniqueId();
        });
    }
}
