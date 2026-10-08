<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaResource extends Model
{
    protected $fillable = ['name', 'type', 'instagram_media_id', 'permalink', 'destination_url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AutomationRule::class);
    }
}
