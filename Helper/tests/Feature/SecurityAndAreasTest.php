<?php

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Document;
use App\Models\User;
use App\Support\LoginThrottle;

beforeEach(function () {
    $this->a = Area::create(['name' => 'Verificaciones', 'status' => 'active']);
    $this->b = Area::create(['name' => 'Validaciones', 'status' => 'active']);
    $this->c = Area::create(['name' => 'Calidad', 'status' => 'active']);
});

function modWith(Area ...$areas): User
{
    $mod = User::factory()->create(['role' => UserRole::Mod, 'status' => 'active', 'area_id' => $areas[0]->id]);
    $mod->areas()->sync(collect($areas)->pluck('id')->all());

    return $mod;
}

function docIn(Area $area): Document
{
    $owner = User::factory()->create(['role' => UserRole::User, 'status' => 'active', 'area_id' => $area->id]);

    return Document::create([
        'title' => 'Informe', 'code' => 'DOC-X', 'status' => 'draft', 'user_id' => $owner->id,
        'area_id' => $area->id, 'file_path' => 'x', 'doc_key' => 'k'.uniqid(), 'version' => 1,
    ]);
}

it('lets a moderator supervise every one of their areas and nothing else', function () {
    $mod = modWith($this->a, $this->b);

    expect($mod->can('view', docIn($this->a)))->toBeTrue()
        ->and($mod->can('view', docIn($this->b)))->toBeTrue()
        ->and($mod->can('view', docIn($this->c)))->toBeFalse();

    expect(Document::query()->supervisedBy($mod)->count())->toBe(2);
});

it('does not give access through a suspended area', function () {
    $mod = modWith($this->a, $this->b);
    $this->b->update(['status' => 'suspended']);

    expect($mod->fresh()->areaIds())->toBe([$this->a->id])
        ->and($mod->fresh()->accessDeniedReason())->toBeNull();

    $this->a->update(['status' => 'suspended']);

    expect($mod->fresh()->accessDeniedReason())->not->toBeNull();
});

it('locks the login for 30 minutes after 5 wrong passwords, even with the right one', function () {
    $user = User::factory()->create(['role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id]);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'mala-'.$i]);
    }

    expect(LoginThrottle::lockedFor($user->email))->toBeGreaterThan(29 * 60);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->travel(31)->minutes();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticated();
});

it('logs out after the idle limit and keeps the session alive with activity', function () {
    $user = User::factory()->create(['role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id]);

    $this->actingAs($user)->get('/documents')->assertOk();

    $this->travel(10)->minutes();
    $this->get('/documents')->assertOk(); // actividad: reinicia el reloj

    $this->travel(17)->minutes();
    $this->get('/documents')->assertRedirect(route('login'));
    $this->assertGuest();
});
