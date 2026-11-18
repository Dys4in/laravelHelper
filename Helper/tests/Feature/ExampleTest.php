<?php

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;

it('serves the login page with no-cache headers', function () {
    $this->get('/login')
        ->assertOk()
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
});

it('audit trail has no live toggle', function () {
    $area = Area::create(['name' => 'Auditoria', 'status' => 'active']);
    $admin = User::factory()->create([
        'role' => UserRole::Admin, 'status' => 'active', 'area_id' => $area->id,
    ]);

    $this->actingAs($admin)->get(route('audit.index'))
        ->assertOk()
        ->assertDontSee('En vivo')
        ->assertDontSee('audit-live');
});

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
