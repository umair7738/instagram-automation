<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationExecution extends Model
{
    protected $fillable = ['automation_rule_id', 'contact_id', 'media_resource_id', 'idempotency_key', 'origin', 'status', 'context', 'processed_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'processed_at' => 'datetime'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function outgoingMessages(): HasMany
    {
        return $this->hasMany(OutgoingMessage::class);
    }
}
