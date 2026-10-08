<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingMessage extends Model
{
    protected $fillable = ['automation_execution_id', 'contact_id', 'message_template_id', 'type', 'body', 'target_id', 'idempotency_key', 'status', 'external_message_id', 'sent_at', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'sent_at' => 'datetime'];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'automation_execution_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
