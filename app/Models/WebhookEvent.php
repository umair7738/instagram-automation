<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    protected $fillable = ['instagram_account_id', 'event_key', 'payload', 'status', 'processed_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'instagram_account_id');
    }
}
