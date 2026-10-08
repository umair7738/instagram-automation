<?php

namespace Tests\Feature;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Contact;
use App\Models\InstagramAccount;
use App\Models\Interaction;
use App\Models\MediaResource;
use App\Models\OutgoingMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_operational_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk()
            ->assertSee('Your automation at a glance')
            ->assertSee('Setup and system health')
            ->assertSee('Instagram DM capability')
            ->assertSee('Toggle navigation');
    }

    public function test_authenticated_user_can_inspect_delivery_activity(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('activity.index', ['view' => 'messages']));

        $response->assertOk()
            ->assertSee('Automation activity')
            ->assertSee('Outgoing actions')
            ->assertSee('No outgoing actions found');
    }

    public function test_activity_separates_comment_text_public_reply_and_private_dm_status(): void
    {
        $account = InstagramAccount::create([
            'name' => 'Test Instagram',
            'instagram_user_id' => '17840000000000001',
            'username' => 'test_account',
            'access_token' => 'token',
        ]);
        $media = MediaResource::create([
            'name' => 'Test Reel',
            'type' => 'media',
            'instagram_media_id' => '18000000000000001',
            'permalink' => 'https://instagram.com/reel/test',
        ]);
        $rule = AutomationRule::create([
            'instagram_account_id' => $account->id,
            'media_resource_id' => $media->id,
            'name' => 'Comment rule',
            'trigger_type' => 'comment_keyword',
            'keyword' => 'info',
            'public_reply' => 'Thanks!',
            'is_active' => true,
        ]);
        $contact = Contact::create([
            'instagram_account_id' => $account->id,
            'instagram_scoped_id' => '1122334455',
            'username' => 'commenter_one',
        ]);
        $execution = AutomationExecution::create([
            'automation_rule_id' => $rule->id,
            'contact_id' => $contact->id,
            'media_resource_id' => $media->id,
            'idempotency_key' => 'test-activity-execution',
            'origin' => 'comment',
            'status' => 'processed',
            'context' => ['comment_id' => 'comment-1'],
        ]);
        Interaction::create([
            'contact_id' => $contact->id,
            'instagram_account_id' => $account->id,
            'media_resource_id' => $media->id,
            'automation_rule_id' => $rule->id,
            'automation_execution_id' => $execution->id,
            'type' => 'comment',
            'source_media_id' => $media->instagram_media_id,
            'source_comment_id' => 'comment-1',
            'payload' => ['from' => ['username' => 'commenter_one'], 'text' => 'INFO please'],
        ]);
        OutgoingMessage::create([
            'automation_execution_id' => $execution->id,
            'contact_id' => $contact->id,
            'type' => 'public_comment_reply',
            'body' => 'Thanks!',
            'target_id' => 'comment-1',
            'idempotency_key' => 'test-public-reply',
            'status' => 'sent',
        ]);
        OutgoingMessage::create([
            'automation_execution_id' => $execution->id,
            'contact_id' => $contact->id,
            'type' => 'private_comment_reply',
            'body' => 'Here is the link',
            'target_id' => 'comment-1',
            'idempotency_key' => 'test-private-reply',
            'status' => 'failed',
            'meta' => ['error' => ['message' => 'Permission denied']],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('activity.index', ['view' => 'comments']));

        $response->assertOk()
            ->assertSee('@commenter_one')
            ->assertSee('INFO please')
            ->assertSee('sent')
            ->assertSee('failed')
            ->assertSee('Check Meta DM permissions');
    }

    public function test_rule_builder_explains_meta_dm_dependency(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('rules.create'));

        $response->assertOk()
            ->assertSee('Create automation')
            ->assertSee('Private messages require Meta messaging capability')
            ->assertSee('Message preview');
    }
}
