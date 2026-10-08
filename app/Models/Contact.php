<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = ['instagram_account_id', 'instagram_scoped_id', 'username', 'name', 'last_interacted_at'];

    protected function casts(): array
    {
        return ['last_interacted_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'instagram_account_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }
}
