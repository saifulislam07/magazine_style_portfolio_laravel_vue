<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['data'])]
class BookSetting extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
