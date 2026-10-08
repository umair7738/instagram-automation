<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = ['instagram_account_id', 'media_resource_id', 'message_template_id', 'resource_id', 'name', 'trigger_type', 'keyword', 'public_reply', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'instagram_account_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaResource::class, 'media_resource_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(MediaResource::class, 'resource_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class);
    }
}
