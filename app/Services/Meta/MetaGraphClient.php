<?php

namespace App\Services\Meta;

use App\Models\InstagramAccount;
use App\Models\OutgoingMessage;
use Illuminate\Support\Facades\Http;

class MetaGraphClient
{
    public function listMedia(InstagramAccount $account): array
    {
        $baseUrl = $account->auth_mode === 'instagram_login'
            ? config('meta.instagram_api_base_url')
            : config('meta.api_base_url');
        $version = $account->auth_mode === 'instagram_login'
            ? config('meta.instagram_graph_version')
            : config('meta.graph_version');

        return Http::baseUrl($baseUrl)
            ->withToken((string) $account->access_token)
            ->get('/'.$version.'/'.$account->instagram_user_id.'/media', [
                'fields' => 'id,permalink,caption,media_type,media_product_type,media_url,thumbnail_url,timestamp',
                'limit' => 25,
            ])
            ->throw()
            ->json('data', []);
    }

    public function send(OutgoingMessage $message, InstagramAccount $account): array
    {
        $path = match ($message->type) {
            'public_comment_reply' => $message->target_id.'/replies',
            // Instagram Messaging uses the connected professional account's
            // Messages edge; the comment id in the recipient opens a private
            // reply to that specific comment.
            'private_comment_reply', 'direct_message' => $account->instagram_user_id.'/messages',
            default => throw new \InvalidArgumentException('Unsupported Instagram message type: '.$message->type),
        };

        $payload = match ($message->type) {
            'public_comment_reply' => ['message' => $message->body],
            'private_comment_reply' => [
                'recipient' => ['comment_id' => $message->target_id],
                'message' => ['text' => $message->body],
            ],
            'direct_message' => [
                'recipient' => ['id' => $message->target_id],
                'message' => ['text' => $message->body],
            ],
            default => [],
        };

        $baseUrl = $account->auth_mode === 'instagram_login'
            ? config('meta.instagram_api_base_url')
            : config('meta.api_base_url');
        $version = $account->auth_mode === 'instagram_login'
            ? config('meta.instagram_graph_version')
            : config('meta.graph_version');

        return Http::baseUrl($baseUrl)->post('/'.$version.'/'.$path, $payload + ['access_token' => $account->access_token])->throw()->json();
    }
}
