<?php

namespace App\Services\Automation;

use App\Models\OutgoingMessage;

class MessagingPolicy
{
    public function permits(OutgoingMessage $message): bool
    {
        return in_array($message->type, ['public_comment_reply', 'private_comment_reply', 'direct_message'], true) && filled($message->body) && filled($message->target_id);
    }
}
