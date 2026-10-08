<?php

namespace Tests\Feature;

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

    public function test_rule_builder_explains_meta_dm_dependency(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('rules.create'));

        $response->assertOk()
            ->assertSee('Create automation')
            ->assertSee('Private messages require Meta messaging capability')
            ->assertSee('Message preview');
    }
}
