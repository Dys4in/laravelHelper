<?php

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use App\Support\LoginThrottle;
use App\Support\OnlyOfficeService;
use Illuminate\Support\Facades\Hash;

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

it('lets a coordinator supervise every one of their areas and nothing else', function () {
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

it('rejects duplicated user names on create and update', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $existing = User::factory()->create(['name' => 'Juan Perez', 'status' => 'active', 'area_id' => $this->a->id]);
    $target = User::factory()->create(['status' => 'active', 'area_id' => $this->a->id]);

    $payload = [
        'name' => $existing->name,
        'signature' => 'J.C. Pérez',
        'email' => 'nuevo@example.com',
        'role' => UserRole::User->value,
        'area_id' => $this->a->id,
        'password' => 'clave-nueva-123',
        'password_confirmation' => 'clave-nueva-123',
    ];

    $this->actingAs($admin)->post(route('admin.users.store'), $payload)
        ->assertSessionHasErrors('name');

    $payload['email'] = $target->email;
    $payload['password'] = '';
    $payload['password_confirmation'] = '';

    $this->actingAs($admin)->patch(route('admin.users.update', $target), $payload)
        ->assertSessionHasErrors('name');
});

it('requires the GMP signature when creating a user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $payload = [
        'name' => 'Usuario Con Firma',
        'email' => 'con.firma@example.com',
        'role' => UserRole::User->value,
        'area_id' => $this->a->id,
        'status' => 'active',
    ];

    $this->actingAs($admin)->post(route('admin.users.store'), $payload)
        ->assertSessionHasErrors('signature');

    $payload['signature'] = 'J.C. Pérez';

    $this->actingAs($admin)->post(route('admin.users.store'), $payload)
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'con.firma@example.com',
        'signature' => 'J.C. Pérez',
        'must_change_password' => true,
    ]);
});

it('does not save a user edit when nothing changed', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $target = User::factory()->create([
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id,
    ]);

    $this->actingAs($admin)->patch(route('admin.users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::User->value,
        'status' => 'active',
        'area_id' => $this->a->id,
        'password' => '',
        'password_confirmation' => '',
    ])
        ->assertRedirect()
        ->assertSessionHas('status', 'No hay cambios para guardar.');
});

it('still saves a password-only user edit', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $target = User::factory()->create([
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id,
    ]);

    $this->actingAs($admin)->patch(route('admin.users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::User->value,
        'status' => 'active',
        'area_id' => $this->a->id,
        'password' => 'clave-nueva-123',
        'password_confirmation' => 'clave-nueva-123',
    ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'Usuario actualizado correctamente.');

    expect(Hash::check('clave-nueva-123', $target->fresh()->password))->toBeTrue();
});

it('does not save an area edit when nothing changed', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $area = Area::create(['name' => 'Prueba', 'description' => 'AB', 'status' => 'active']);

    $this->actingAs($admin)->patch(route('admin.areas.update', $area), [
        'name' => 'Prueba',
        'description' => 'AB',
    ])
        ->assertRedirect()
        ->assertSessionHas('status', 'No hay cambios para guardar.');
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

it('gives the jefe de area the same access as an administrator', function () {
    $jefe = User::factory()->create(['role' => UserRole::JefeArea, 'status' => 'active', 'area_id' => $this->a->id]);
    $doc = docIn($this->c);

    expect($jefe->isAdmin())->toBeTrue()
        ->and($jefe->can('view', $doc))->toBeTrue()
        ->and($jefe->can('changeStatus', $doc))->toBeTrue()
        ->and(Document::query()->supervisedBy($jefe)->count())->toBe(1);

    $this->actingAs($jefe)->get(route('admin.dashboard'))->assertOk();
    $this->get(route('supervision.index'))->assertOk();
});

it('hides the administrator role from the user form', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

    preg_match('/<select[^>]*data-role-select[^>]*>(.*?)<\/select>/s', $html, $matches);
    $options = $matches[1] ?? '';

    expect($options)->not->toContain('value="admin"')
        ->and($options)->toContain('value="jefe_de_area"')
        ->and($html)->toContain("role.value === 'jefe_de_area'");
});

it('creates a jefe de area without an assigned area', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Jefe Prueba',
        'signature' => 'Jefe P.',
        'email' => 'jefe@example.com',
        'role' => UserRole::JefeArea->value,
        'status' => 'active',
        'password' => 'password',
        'password_confirmation' => 'password',
        'area_id' => $this->a->id,
    ])->assertRedirect(route('admin.users.index'));

    $jefe = User::where('email', 'jefe@example.com')->firstOrFail();

    expect($jefe->area_id)->toBeNull()
        ->and($jefe->areas()->count())->toBe(0);
});

it('does not allow editing or deactivating an administrator account', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $jefe = User::factory()->create(['role' => UserRole::JefeArea, 'status' => 'active']);

    $html = $this->actingAs($jefe)->get(route('admin.users.index'))->assertOk()->getContent();

    expect($html)->toContain('Cuenta protegida')
        ->and($html)->not->toContain('data-modal-open="edit-user-'.$admin->id.'"');

    $this->patch(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Admin->value,
        'status' => 'inactive',
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect()->assertSessionHas('error', 'La cuenta de administrador no se puede editar.');

    $this->patch(route('admin.users.status', $admin))
        ->assertRedirect()
        ->assertSessionHas('error', 'La cuenta de administrador no se puede desactivar.');

    $this->get(route('admin.users.edit', $admin))->assertRedirect(route('admin.users.index'));

    expect($admin->fresh()->status)->toBe('active');
});

it('lets every user upload, edit and delete templates only for their own areas', function () {
    $user = User::factory()->create(['role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id]);

    $template = Template::create([
        'name' => 'Del area',
        'file_path' => 'x',
        'original_name' => 'x.docx',
        'status' => 'inactive',
        'area_id' => $this->a->id,
    ]);
    $foreign = Template::create([
        'name' => 'Ajena', 'file_path' => 'x', 'original_name' => 'x.docx',
        'status' => 'active', 'area_id' => $this->b->id,
    ]);
    $general = Template::create([
        'name' => 'General', 'file_path' => 'x', 'original_name' => 'x.docx',
        'status' => 'active', 'area_id' => null,
    ]);

    $html = $this->actingAs($user)->get(route('templates.index'))->assertOk()->getContent();

    expect($user->can('create', Template::class))->toBeTrue()
        ->and($user->can('manage', $template))->toBeTrue()
        ->and($user->can('manage', $foreign))->toBeFalse()
        ->and($user->can('manage', $general))->toBeFalse()
        ->and($html)->toContain('Subir plantilla')
        ->and($html)->toContain('data-modal-open="edit-template-'.$template->id.'"')
        ->and($html)->toContain(route('templates.destroy', $template))
        ->and($html)->not->toContain(route('templates.destroy', $foreign));

    $this->get(route('templates.edit', $template))->assertOk();
    $this->get(route('templates.edit', $foreign))->assertForbidden();
    $this->delete(route('templates.destroy', $template))->assertRedirect();
    $this->delete(route('templates.destroy', $foreign))->assertForbidden();
    $this->delete(route('templates.destroy', $general))->assertForbidden();

    expect($template->fresh()->trashed())->toBeTrue();

    $this->post(route('templates.store'), [
        'name' => 'Ajena',
        'area_id' => $this->b->id,
        'status' => 'active',
    ])->assertSessionHasErrors('area_id');
});

it('does not let users edit their signature', function () {
    $user = User::factory()->create([
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id,
        'signature' => 'J.C. Pérez',
    ]);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('J.C. Pérez')
        ->assertDontSee('name="signature"', false);

    $this->patch(route('profile.update'), [
        'name' => $user->name,
        'signature' => 'Otra firma',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->signature)->toBe('J.C. Pérez');
});

it('does not let an administrator edit a user signature', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);
    $user = User::factory()->create([
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id,
        'signature' => 'J.C. Pérez',
    ]);

    $this->actingAs($admin)->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertSee('J.C. Pérez')
        ->assertDontSee('name="signature"', false);

    $this->actingAs($admin)->patch(route('admin.users.update', $user), [
        'name' => $user->name,
        'signature' => 'Firma HC',
        'email' => $user->email,
        'role' => UserRole::User->value,
        'area_id' => $this->a->id,
        'status' => 'active',
        'password' => '',
        'password_confirmation' => '',
    ])->assertSessionHasErrors('signature');

    expect($user->fresh()->signature)->toBe('J.C. Pérez');
});

it('lets a coordinator view and create users only in their areas', function () {
    $coordinator = modWith($this->a, $this->b);
    $inA = User::factory()->create(['name' => 'Usuario A', 'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id]);
    $inB = User::factory()->create(['name' => 'Usuario B', 'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->b->id]);
    $inC = User::factory()->create(['name' => 'Usuario C', 'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->c->id]);
    $admin = User::factory()->create(['name' => 'Administrador', 'role' => UserRole::Admin, 'status' => 'active']);

    $response = $this->actingAs($coordinator)->get(route('admin.users.index'))->assertOk();
    $ids = $response->viewData('users')->pluck('id')->all();

    expect($ids)->toContain($inA->id, $inB->id)
        ->and($ids)->not->toContain($inC->id, $admin->id)
        ->and($response->viewData('formRoles'))->toBe([UserRole::User]);

    $this->post(route('admin.users.store'), [
        'name' => 'Nuevo del area',
        'signature' => 'Nuevo A.',
        'email' => 'nuevo.del.area@example.com',
        'role' => UserRole::User->value,
        'area_id' => $this->a->id,
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Nuevo del area',
        'area_id' => $this->a->id,
        'role' => UserRole::User->value,
    ]);
});

it('does not let a coordinator create privileged users or users outside their areas', function () {
    $coordinator = modWith($this->a);

    $this->actingAs($coordinator)->post(route('admin.users.store'), [
        'name' => 'Nuevo admin',
        'signature' => 'Nuevo A.',
        'email' => 'nuevo.admin@example.com',
        'role' => UserRole::Admin->value,
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertForbidden();

    $this->post(route('admin.users.store'), [
        'name' => 'Fuera de area',
        'signature' => 'Fuera A.',
        'email' => 'fuera.de.area@example.com',
        'role' => UserRole::User->value,
        'area_id' => $this->c->id,
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertForbidden();

    $this->get(route('admin.users.edit', $coordinator))->assertForbidden();
    $this->patch(route('admin.users.status', $coordinator))
        ->assertRedirect()
        ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');
    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->get(route('admin.areas.index'))->assertForbidden();
});

it('lets coordinators edit documents from their areas with review disabled', function () {
    $coordinator = modWith($this->a, $this->b);
    $document = docIn($this->a);
    $foreign = docIn($this->c);
    $owner = $document->user;
    $peer = User::factory()->create(['role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id]);

    $completed = docIn($this->a);
    $completed->update(['status' => Document::COMPLETED]);

    expect($coordinator->can('edit', $document))->toBeTrue()
        ->and($coordinator->can('edit', $foreign))->toBeFalse()
        ->and($coordinator->can('edit', $completed))->toBeFalse()
        ->and($coordinator->can('review', $document))->toBeFalse()
        ->and($owner->can('edit', $document))->toBeTrue()
        ->and($peer->can('edit', $document))->toBeFalse();

    $config = app(OnlyOfficeService::class)->editorConfig($document, $coordinator, 'edit');

    expect($config['document']['permissions']['review'])->toBeFalse()
        ->and($config['document']['permissions']['edit'])->toBeTrue();
});

it('lets a coordinator edit users in their areas without changing roles or areas', function () {
    $coordinator = modWith($this->a, $this->b);
    $target = User::factory()->create([
        'name' => 'Usuario editable', 'email' => 'editable@example.com',
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->a->id,
        'signature' => 'Firma original',
    ]);
    $foreign = User::factory()->create([
        'name' => 'Usuario ajeno', 'email' => 'ajeno@example.com',
        'role' => UserRole::User, 'status' => 'active', 'area_id' => $this->c->id,
    ]);
    $peer = modWith($this->a);

    $html = $this->actingAs($coordinator)->get(route('admin.users.index'))->assertOk()->getContent();

    expect($html)->toContain('data-modal-open="edit-user-'.$target->id.'"')
        ->and($html)->not->toContain('data-modal-open="edit-user-'.$peer->id.'"');

    $this->get(route('admin.users.edit', $target))->assertOk();
    $this->get(route('admin.users.edit', $foreign))->assertForbidden();
    $this->get(route('admin.users.edit', $peer))->assertForbidden();

    $payload = [
        'name' => 'Usuario editado',
        'email' => $target->email,
        'role' => UserRole::User->value,
        'area_id' => $this->a->id,
        'status' => 'active',
        'password' => '',
        'password_confirmation' => '',
    ];

    $this->patch(route('admin.users.update', $target), $payload)->assertRedirect(route('admin.users.index'));
    expect($target->fresh()->name)->toBe('Usuario editado')
        ->and($target->fresh()->signature)->toBe('Firma original');

    $this->patch(route('admin.users.update', $target), array_merge($payload, [
        'role' => UserRole::Mod->value,
        'areas' => [$this->a->id],
    ]))->assertForbidden();
    $this->patch(route('admin.users.update', $target), array_merge($payload, ['area_id' => $this->c->id]))->assertForbidden();
    $this->patch(route('admin.users.status', $target))->assertRedirect();
    expect($target->fresh()->status)->toBe('inactive');

    $this->patch(route('admin.users.status', $foreign))->assertForbidden();
    $this->patch(route('admin.users.status', $peer))->assertForbidden();
});
