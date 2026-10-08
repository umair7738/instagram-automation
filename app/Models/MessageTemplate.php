<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function render(array $values = []): string
    {
        return str_replace(array_map(fn ($key) => '{'.$key.'}', array_keys($values)), array_values($values), $this->body);
    }
}
