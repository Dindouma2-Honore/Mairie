<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'service'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', $this->formData(new User(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $temporary = Str::password(12, symbols: false);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'password' => $temporary,
            'must_change_password' => true,
        ]);
        $user->roles()->sync($data['roles']);
        Audit::log('user.roles_assigned', $user, null, ['roles' => Role::whereIn('id', $data['roles'])->pluck('slug')->all()]);

        // Le mot de passe temporaire n'est affiché qu'une seule fois.
        return redirect()->route('admin.users.index')
            ->with('success', 'Compte créé pour ' . $user->name . '.')
            ->with('temporary_password', ['email' => $user->email, 'password' => $temporary]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', $this->formData($user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        // On ne se retire pas soi-même l'accès, sous peine de verrouiller l'administration.
        if ($user->is($request->user()) && ! $request->boolean('is_active', true)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $before = $user->roles()->pluck('roles.id')->sort()->values()->all();
        $after = collect($data['roles'])->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($before !== $after) {
            $user->roles()->sync($data['roles']);
            Audit::log('user.roles_changed', $user, ['roles' => $before], ['roles' => $after]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Compte mis à jour.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $temporary = Str::password(12, symbols: false);
        $user->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
        Audit::log('user.password_reset', $user);

        return back()->with('success', 'Mot de passe réinitialisé pour ' . $user->name . '.')
            ->with('temporary_password', ['email' => $user->email, 'password' => $temporary]);
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'selected' => $user->exists ? $user->roles()->pluck('roles.id')->all() : [],
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'service_id' => ['nullable', 'exists:services,id'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ], [], ['name' => 'nom', 'roles' => 'rôles', 'service_id' => 'service']);
    }
}
