<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['visited_on', 'views'])]
class PageVisit extends Model
{
    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
            'views' => 'integer',
        ];
    }

    /**
     * Count one view for today.
     */
    public static function recordToday(): void
    {
        $today = now()->toDateString();

        static::query()->insertOrIgnore([
            'visited_on' => $today,
            'views' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        static::query()->where('visited_on', $today)->update([
            'views' => DB::raw('views + 1'),
            'updated_at' => now(),
        ]);
    }
}
