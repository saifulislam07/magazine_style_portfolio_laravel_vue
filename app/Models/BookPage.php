<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'kind', 'label', 'position', 'content'])]
class BookPage extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'content' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBookData(): array
    {
        return [
            'id' => $this->slug,
            'kind' => $this->kind,
            'label' => $this->label,
            'position' => $this->position,
            ...($this->content ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminData(): array
    {
        return [
            'id' => $this->slug,
            'kind' => $this->kind,
            'label' => $this->label,
            'position' => $this->position,
            'content' => $this->content ?? [],
        ];
    }
}
