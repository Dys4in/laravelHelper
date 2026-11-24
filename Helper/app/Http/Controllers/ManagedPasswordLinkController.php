<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\CorporatePassword;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagedPasswordLinkController extends Controller {
    public function generate(Request $request, User $user): RedirectResponse {
        $actor = $request->user();
        abort_unless($this->canManage($actor, $user), 403);
        abort_if($user->is($actor), 403, 'Utiliza tu perfil para modificar tu contraseña.');
        $token = Str::random(80);
        DB::transaction(function () use ($user, $actor, $token) {
            // Serialize concurrent link generations for the same account.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            DB::table('managed_password_links')->where('user_id', $user->id)->whereNull('used_at')->delete();
            DB::table('managed_password_links')->insert([
                'user_id' => $user->id, 'created_by' => $actor->id,
                'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        // Display only once, never persist the plaintext token in logs or database.
        return back()->with('recovery_link', route('managed-password.show', ['token' => $token]));
    }

    public function show(Request $request, string $token): View {
        $countVisit = ! $request->session()->pull('managed_password_validation_failed', false);
        $record = DB::transaction(function () use ($token, $countVisit) {
            $record = DB::table('managed_password_links')->where('token_hash', hash('sha256', $token))
                ->where('expires_at', '>', now())->lockForUpdate()->first();
            if (! $record || ($countVisit && $record->visits_count >= 2)) {
                abort(404, 'Enlace inválido o caducado.');
            }
            if ($countVisit) {
                DB::table('managed_password_links')->where('id', $record->id)->increment('visits_count');
                $record->visits_count++;
            }
            return $record;
        });
        abort_unless(User::whereKey($record->user_id)->where('status', 'active')->exists(), 404, 'Enlace inválido o caducado.');
        return view('auth.managed-password', ['token' => $token, 'alreadyUsed' => (bool) $record->used_at]);
    }

    public function update(Request $request, string $token): RedirectResponse {
        try {
            $hash = hash('sha256', $token);
            $result = DB::transaction(function () use ($request, $hash) {
                $record = DB::table('managed_password_links')->where('token_hash', $hash)->lockForUpdate()->first();
                if (! $record || $record->used_at || $record->visits_count > 2 || now()->greaterThanOrEqualTo($record->expires_at)) return false;
                $user = User::whereKey($record->user_id)->lockForUpdate()->first();
                if (! $user || ! $user->isActive()) return false;
                $data = $request->validate(['password' => ['required', 'string', 'confirmed', new CorporatePassword($user)]]);
                if (Hash::check($data['password'], $user->password)) throw ValidationException::withMessages(['password' => 'Elige una contraseña diferente a la anterior.']);
                $user->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false, 'remember_token' => Str::random(60)])->save();
                DB::table('managed_password_links')->where('id', $record->id)->update(['used_at' => now(), 'updated_at' => now()]);
                DB::table('managed_password_links')->where('user_id', $user->id)->where('id', '<>', $record->id)->delete();
                // Invalidate sessions stored in DB, if this deployment uses database sessions.
                if (config('session.driver') === 'database') DB::table('sessions')->where('user_id', $user->id)->delete();
                return true;
            });
            if (! $result) throw ValidationException::withMessages(['password' => 'Este enlace ya fue utilizado o venció. Solicita uno nuevo.']);
            return redirect()->route('login')->with('status', 'Contraseña actualizada. Inicia sesión con tu nueva clave.');
        } catch (ValidationException $e) {
            $request->session()->flash('managed_password_validation_failed', true);
            throw $e;
        }
    }

    private function canManage(User $actor, User $target): bool {
        if ($actor->isAdmin()) return ! $target->hasRole('admin');
        if (! $actor->hasRole('mod') || ! $target->hasRole('user')) return false;
        return count(array_intersect($actor->areaIds(), $target->assignedAreas()->pluck('id')->map(fn ($id) => (int) $id)->all())) > 0;
    }
}
