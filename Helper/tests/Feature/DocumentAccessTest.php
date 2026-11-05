<?php

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function makeUser(UserRole $role, ?Area $area): User
{
    return User::factory()->create([
        'role' => $role,
        'status' => 'active',
        'area_id' => $area?->id,
    ]);
}

function makeDoc(User $owner, string $status = 'draft'): Document
{
    Storage::disk(config('onlyoffice.disk'))->put($path = 'documents/'.uniqid().'.docx', 'fake');

    return Document::create([
        'title' => 'Informe', 'code' => 'DOC-TEST', 'status' => $status,
        'user_id' => $owner->id, 'area_id' => $owner->area_id,
        'file_path' => $path, 'doc_key' => 'k'.uniqid(), 'version' => 1,
    ]);
}

beforeEach(function () {
    Storage::fake(config('onlyoffice.disk'));
    $this->area = Area::create(['name' => 'Verificaciones', 'status' => 'active']);
    $this->other = Area::create(['name' => 'Validaciones', 'status' => 'active']);
});

test('draft documents cannot be downloaded, completed ones can', function () {
    $user = makeUser(UserRole::User, $this->area);

    $this->actingAs($user)->get(route('documents.download', makeDoc($user, 'draft')))->assertForbidden();
    $this->actingAs($user)->get(route('documents.download', makeDoc($user, 'completed')))->assertOk();
});

test('a user cannot open or download documents of other users, even in their own area', function () {
    $user = makeUser(UserRole::User, $this->area);
    $peer = makeUser(UserRole::User, $this->area);
    $doc = makeDoc($peer, 'completed');

    $this->actingAs($user)->get(route('documents.download', $doc))->assertForbidden();
    $this->actingAs($user)->get(route('documents.edit', $doc))->assertForbidden();
});

test('only drafts can be deleted, and only by the author or an admin', function () {
    $user = makeUser(UserRole::User, $this->area);
    $peer = makeUser(UserRole::User, $this->area);
    $admin = makeUser(UserRole::Admin, null);

    $draft = makeDoc($user, 'draft');
    $done = makeDoc($user, 'completed');

    $this->actingAs($peer)->delete(route('documents.destroy', $draft))->assertForbidden();
    $this->actingAs($user)->delete(route('documents.destroy', $done))->assertForbidden();
    $this->actingAs($admin)->delete(route('documents.destroy', $done))->assertForbidden();

    $this->actingAs($user)->delete(route('documents.destroy', $draft))->assertRedirect();
    expect(Document::find($draft->id))->toBeNull()
        ->and(Storage::disk(config('onlyoffice.disk'))->exists($draft->file_path))->toBeFalse();
});

test('the supervision module is closed to plain users and scoped to the moderator area', function () {
    $user = makeUser(UserRole::User, $this->area);
    $mod = makeUser(UserRole::Mod, $this->area);
    $foreign = makeDoc(makeUser(UserRole::User, $this->other));
    $own = makeDoc($user);

    $this->actingAs($user)->get(route('documents.index'))->assertForbidden();

    $response = $this->actingAs($mod)->get(route('documents.index'))->assertOk();
    expect($response->viewData('documents')->pluck('id')->all())->toBe([$own->id])
        ->and($response->viewData('documents')->pluck('id'))->not->toContain($foreign->id);
});

test('users only see active templates assigned to their area', function () {
    $user = makeUser(UserRole::User, $this->area);

    $mine = Template::create(['name' => 'Mia', 'area_id' => $this->area->id, 'file_path' => 'x', 'original_name' => 'x.docx', 'status' => 'active']);
    Template::create(['name' => 'Ajena', 'area_id' => $this->other->id, 'file_path' => 'x', 'original_name' => 'x.docx', 'status' => 'active']);
    Template::create(['name' => 'Inactiva', 'area_id' => $this->area->id, 'file_path' => 'x', 'original_name' => 'x.docx', 'status' => 'inactive']);

    $ids = $this->actingAs($user)->get(route('templates.index'))->assertOk()->viewData('templates')->pluck('id')->all();

    expect($ids)->toBe([$mine->id]);
    $this->actingAs($user)->post(route('templates.store'), ['name' => 'x'])->assertForbidden();
});

test('remember me keeps the email in a cookie and unchecked forgets it', function () {
    $user = makeUser(UserRole::User, $this->area);

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
        ->assertCookie('helper_remembered_email');

    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertCookieExpired('helper_remembered_email');
});
