<?php

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function rotationUser(): User
{
    $area = Area::create(['name' => 'Pruebas', 'status' => 'active']);

    return User::factory()->create([
        'name' => 'Usuario Prueba',
        'email' => 'usuario.prueba@example.com',
        'role' => UserRole::User,
        'status' => 'active',
        'area_id' => $area->id,
        'password_changed_at' => now()->subMonths(4),
    ]);
}

it('asks for a new password after three months', function () {
    $user = rotationUser();

    expect($user->passwordExpired())->toBeTrue()
        ->and($user->needsNewPassword())->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.first.edit'));

    $this->get(route('password.first.edit'))
        ->assertOk()
        ->assertSee('Actualiza tu contraseña');
});

it('resumes normal access after changing an expired password', function () {
    $user = rotationUser();

    $this->actingAs($user)->post(route('password.first.update'), [
        'current_password' => 'password',
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ])->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->needsNewPassword())->toBeFalse()
        ->and($user->passwordExpired())->toBeFalse()
        ->and($user->password_changed_at->isToday())->toBeTrue();

    $this->actingAs($user->refresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('documents.index'));
});

function firstLoginUser(): User
{
    $area = Area::create(['name' => 'Pruebas', 'status' => 'active']);

    return User::factory()->create([
        'name' => 'Usuario Prueba',
        'email' => 'usuario.prueba@example.com',
        'role' => UserRole::User,
        'status' => 'active',
        'area_id' => $area->id,
        'signature' => 'J.C. Pérez',
        'must_change_password' => true,
    ]);
}

it('asks for the GMP signature on first login', function () {
    $user = firstLoginUser();

    $this->actingAs($user)->get(route('password.first.edit'))
        ->assertOk()
        ->assertSee('Firma GMP')
        ->assertSee('name="signature"', false)
        ->assertDontSee('value="J.C. Pérez"', false)
        ->assertDontSee('Contraseña temporal', false);

    $this->post(route('password.first.update'), [
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ])->assertSessionHasErrors('signature');
});

it('accepts the registered GMP signature without changing it', function () {
    $user = firstLoginUser();

    $this->actingAs($user)->post(route('password.first.update'), [
        'signature' => 'J.C. Pérez',
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ])->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->signature)->toBe('J.C. Pérez')
        ->and($user->needsNewPassword())->toBeFalse()
        ->and($user->temporary_password)->toBeNull();
});

it('rejects a GMP signature that does not match the registered one', function () {
    $user = firstLoginUser();

    $this->actingAs($user)->post(route('password.first.update'), [
        'signature' => 'Firma HC',
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ])->assertSessionHasErrors('signature');

    $user->refresh();

    expect($user->signature)->toBe('J.C. Pérez')
        ->and($user->needsNewPassword())->toBeTrue();
});

it('requires the GMP signature when the first password is set through a recovery link', function () {
    $user = firstLoginUser();
    $token = 'token-prueba-firma';

    DB::table('managed_password_links')->insert([
        'user_id' => $user->id,
        'created_by' => $user->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
        'visits_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('managed-password.show', $token))
        ->assertOk()
        ->assertSee('Firma GMP')
        ->assertDontSee('value="J.C. Pérez"', false);

    $payload = [
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ];

    $this->post(route('managed-password.update', $token), $payload)
        ->assertSessionHasErrors('signature');

    $this->post(route('managed-password.update', $token), $payload + ['signature' => 'Firma distinta'])
        ->assertSessionHasErrors('signature');

    $this->post(route('managed-password.update', $token), $payload + ['signature' => 'J.C. Pérez'])
        ->assertRedirect(route('login'));

    $user->refresh();

    expect($user->signature)->toBe('J.C. Pérez')
        ->and($user->needsNewPassword())->toBeFalse();
});
