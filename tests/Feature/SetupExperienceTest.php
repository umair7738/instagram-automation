<?php

namespace Tests\Feature;

use App\Models\AutomationRule;
use App\Models\InstagramAccount;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetupExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_can_open_the_setup_guide(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('setup.index'));

        $response->assertOk()
            ->assertSee('Build your first automation')
            ->assertSee('Connect Instagram')
            ->assertSee('Add a Reel or post')
            ->assertSee('Prepare a message');
    }

    public function test_starter_template_is_copied_as_a_paused_draft(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('templates.examples.duplicate', 'resource-link'));

        $template = MessageTemplate::latest('id')->first();

        $response->assertRedirect(route('templates.edit', $template));
        $this->assertNotNull($template);
        $this->assertFalse($template->is_active);
        $this->assertStringContainsString('{resource_url}', $template->body);
    }

    public function test_starter_rule_creates_a_paused_rule_and_template(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('rules.examples.duplicate', 'comment-info'));

        $rule = AutomationRule::latest('id')->first();

        $response->assertRedirect(route('rules.edit', $rule));
        $this->assertNotNull($rule);
        $this->assertFalse($rule->is_active);
        $this->assertSame('INFO', $rule->keyword);
        $this->assertNotNull($rule->message_template_id);
        $this->assertFalse(MessageTemplate::findOrFail($rule->message_template_id)->is_active);
    }

    public function test_recent_media_import_returns_safe_media_fields(): void
    {
        $account = InstagramAccount::create([
            'name' => 'Demo account',
            'instagram_user_id' => '17841400000000001',
            'username' => 'demo',
            'access_token' => 'encrypted-test-token',
            'is_active' => true,
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [[
                    'id' => '18019326794001472',
                    'permalink' => 'https://www.instagram.com/reel/example/',
                    'caption' => 'Example reel',
                    'media_type' => 'VIDEO',
                    'thumbnail_url' => 'https://example.com/thumb.jpg',
                    'media_url' => 'https://example.com/media.mp4',
                    'timestamp' => '2026-10-09T10:00:00+0000',
                    'access_token' => 'must-not-leak',
                ]],
            ]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('resources.instagram-media', ['account_id' => $account->id]));

        $response->assertOk()
            ->assertJsonPath('data.0.id', '18019326794001472')
            ->assertJsonPath('data.0.permalink', 'https://www.instagram.com/reel/example/')
            ->assertJsonPath('data.0.thumbnail_url', 'https://example.com/thumb.jpg')
            ->assertJsonMissingPath('data.0.access_token');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com')
            && str_contains($request->url(), '/v26.0/17841400000000001/media'));
    }

    public function test_recent_media_import_uses_instagram_login_host(): void
    {
        $account = InstagramAccount::create([
            'name' => 'Instagram Login account',
            'instagram_user_id' => '17841400000000002',
            'username' => 'instagram_login_demo',
            'access_token' => 'encrypted-test-token',
            'auth_mode' => 'instagram_login',
            'is_active' => true,
        ]);

        Http::fake(['graph.instagram.com/*' => Http::response(['data' => []])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('resources.instagram-media', ['account_id' => $account->id]))
            ->assertOk()
            ->assertJson(['data' => []]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.instagram.com')
            && str_contains($request->url(), '/v26.0/17841400000000002/media'));
    }

    public function test_media_resource_requires_numeric_media_id(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('resources.store'), [
                'name' => 'Invalid media',
                'type' => 'media',
                'instagram_media_id' => 'C22CRj7IKWi',
                'permalink' => 'https://www.instagram.com/reel/C22CRj7IKWi/',
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('instagram_media_id');
    }

    public function test_media_import_requires_authentication(): void
    {
        $this->getJson(route('resources.instagram-media', ['account_id' => 1]))
            ->assertUnauthorized();
    }
}
