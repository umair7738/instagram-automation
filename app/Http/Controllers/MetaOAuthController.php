<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaOAuthController extends Controller
{
    public function start(Request $request)
    {
        abort_unless(config('meta.app_id') && config('meta.oauth_redirect'), 503, 'Configure Meta OAuth settings before connecting an account.');
        $state = Str::random(40);
        Cache::put('meta.oauth.state.'.$state, true, now()->addMinutes(10));

        return redirect()->away('https://www.facebook.com/'.config('meta.graph_version').'/dialog/oauth?'.http_build_query([
            'client_id' => config('meta.app_id'),
            'redirect_uri' => config('meta.oauth_redirect'),
            'state' => $state,
            'scope' => 'instagram_basic,instagram_manage_comments,instagram_manage_messages,pages_show_list,pages_read_engagement,pages_manage_metadata,business_management',
        ]));
    }

    public function callback(Request $request)
    {
        $state = (string) $request->string('state');
        abort_unless(filled($state) && Cache::pull('meta.oauth.state.'.$state), 403);
        $token = Http::get(config('meta.api_base_url').'/'.config('meta.graph_version').'/oauth/access_token', [
            'client_id' => config('meta.app_id'),
            'client_secret' => config('meta.app_secret'),
            'redirect_uri' => config('meta.oauth_redirect'),
            'code' => $request->string('code'),
        ])->throw()->json('access_token');
        $page = Http::withToken($token)->get(config('meta.api_base_url').'/'.config('meta.graph_version').'/me/accounts', [
            'fields' => 'id,name,access_token,instagram_business_account',
        ])->throw()->json('data.0');
        abort_unless(data_get($page, 'instagram_business_account.id'), 422, 'No connected Instagram professional account was returned.');
        $instagramUserId = (string) data_get($page, 'instagram_business_account.id');

        InstagramAccount::updateOrCreate(
            ['instagram_user_id' => $instagramUserId],
            [
                'name' => data_get($page, 'name', 'Instagram account'),
                'facebook_page_id' => data_get($page, 'id'),
                'access_token' => data_get($page, 'access_token'),
                'is_active' => true,
            ],
        );

        $this->subscribeInstagramWebhooks(
            $instagramUserId,
            [$token, data_get($page, 'access_token')],
        );

        $this->subscribePageWebhooks(
            (string) data_get($page, 'id'),
            (string) data_get($page, 'access_token'),
        );

        return redirect()->route('accounts.index')->with('success', 'Instagram account connected.');
    }

    public function startInstagram(Request $request)
    {
        abort_unless(config('meta.instagram_app_id') && config('meta.instagram_app_secret'), 503, 'Configure Instagram Login app credentials before connecting an account.');

        $state = Str::random(40);
        Cache::put('instagram.oauth.state.'.$state, true, now()->addMinutes(10));

        return redirect()->away(rtrim(config('meta.instagram_oauth_base_url'), '/').'/oauth/authorize?'.http_build_query([
            'client_id' => config('meta.instagram_app_id'),
            'redirect_uri' => config('meta.instagram_oauth_redirect'),
            'response_type' => 'code',
            'scope' => 'instagram_business_basic,instagram_business_manage_comments,instagram_business_manage_messages',
            'state' => $state,
        ]));
    }

    public function callbackInstagram(Request $request)
    {
        $state = (string) $request->string('state');
        abort_unless(filled($state) && Cache::pull('instagram.oauth.state.'.$state), 403);
        abort_unless(filled($request->string('code')), 422, 'Instagram did not return an authorization code.');

        $shortLived = Http::asForm()->post(rtrim(config('meta.instagram_token_base_url'), '/').'/oauth/access_token', [
            'client_id' => config('meta.instagram_app_id'),
            'client_secret' => config('meta.instagram_app_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('meta.instagram_oauth_redirect'),
            'code' => $request->string('code'),
        ])->throw()->json();

        $shortToken = (string) data_get($shortLived, 'access_token');
        abort_unless($shortToken !== '', 422, 'Instagram did not return an access token.');

        $longLived = Http::get(rtrim(config('meta.instagram_api_base_url'), '/').'/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('meta.instagram_app_secret'),
            'access_token' => $shortToken,
        ])->throw()->json();
        $accessToken = (string) data_get($longLived, 'access_token', $shortToken);

        $profile = Http::withToken($accessToken)->get(
            rtrim(config('meta.instagram_api_base_url'), '/').'/'.config('meta.instagram_graph_version').'/me',
            ['fields' => 'id,username,name'],
        )->throw()->json();
        $instagramUserId = (string) data_get($profile, 'id');
        abort_unless($instagramUserId !== '', 422, 'Instagram did not return an account ID.');

        InstagramAccount::updateOrCreate(
            ['instagram_user_id' => $instagramUserId],
            [
                'name' => data_get($profile, 'name') ?: data_get($profile, 'username', 'Instagram account'),
                'username' => data_get($profile, 'username'),
                'access_token' => $accessToken,
                'auth_mode' => 'instagram_login',
                'is_active' => true,
            ],
        );

        $this->subscribeInstagramLoginWebhooks($instagramUserId, $accessToken);

        return redirect()->route('accounts.index')->with('success', 'Instagram account connected with Instagram Login.');
    }

    private function subscribeInstagramLoginWebhooks(string $instagramUserId, string $accessToken): void
    {
        $url = rtrim(config('meta.instagram_api_base_url'), '/').'/'.config('meta.instagram_graph_version').'/'.$instagramUserId.'/subscribed_apps';
        $response = Http::asForm()->withToken($accessToken)->post($url, [
            'subscribed_fields' => 'comments,messages',
        ]);

        Log::info('Instagram Login webhook subscription attempt', [
            'instagram_user_id' => $instagramUserId,
            'status' => $response->status(),
            'successful' => $response->successful(),
            'response' => $response->json() ?? ['body' => substr($response->body(), 0, 500)],
        ]);
    }

    private function subscribeInstagramWebhooks(string $instagramUserId, array $tokens): void
    {
        $hosts = array_values(array_unique(array_filter([
            config('meta.api_base_url'),
            'https://graph.instagram.com',
        ])));

        foreach ($hosts as $host) {
            $url = rtrim($host, '/').'/'.config('meta.graph_version').'/'.$instagramUserId.'/subscribed_apps';

            foreach (array_values(array_unique(array_filter($tokens))) as $index => $token) {
                $response = Http::asForm()->withToken($token)->post($url, [
                'subscribed_fields' => 'comments,messages',
                ]);

                Log::info('Instagram account webhook subscription attempt', [
                    'instagram_user_id' => $instagramUserId,
                    'host' => parse_url($host, PHP_URL_HOST),
                    'token_type' => $index === 0 ? 'user' : 'page',
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'response' => $response->json() ?? ['body' => substr($response->body(), 0, 500)],
                ]);

                if ($response->status() === 400 && $response->json('error.code') === 3) {
                    Log::warning('Instagram messaging capability is not enabled for this app; Meta must grant messaging access before messages webhooks can be subscribed', [
                        'instagram_user_id' => $instagramUserId,
                        'required_permission' => 'instagram_manage_messages',
                    ]);
                }

                if ($response->successful()) {
                    return;
                }
            }
        }
    }

    private function subscribePageWebhooks(string $pageId, string $pageToken): void
    {
        if ($pageId === '' || $pageToken === '') {
            return;
        }

        $url = rtrim(config('meta.api_base_url'), '/').'/'.config('meta.graph_version').'/'.$pageId.'/subscribed_apps';
        // The Page subscription is used for Instagram comment/feed events.
        // Instagram Direct messages require the separate Instagram messaging
        // capability and must be subscribed on the Instagram object.
        $response = Http::asForm()->withToken($pageToken)->post($url, [
            'subscribed_fields' => 'feed',
        ]);

        Log::info('Facebook Page webhook subscription attempt', [
            'page_id' => $pageId,
            'status' => $response->status(),
            'successful' => $response->successful(),
            'response' => $response->json() ?? ['body' => substr($response->body(), 0, 500)],
        ]);
    }

}
