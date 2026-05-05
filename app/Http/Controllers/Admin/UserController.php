<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->with('roles')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('label')->get(),
            'user' => new User,
            'selectedRoles' => [],
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validatedData($request);
        $roles = $data['roles'];
        unset($data['roles']);

        $user = User::query()->create($data);
        $user->roles()->sync($roles);

        $auditLogger->log('user.created', $request->user(), $user, ['roles' => $roles]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'roles' => Role::query()->orderBy('label')->get(),
            'user' => $user->load('roles'),
            'selectedRoles' => $user->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validatedData($request, $user);
        $roles = $data['roles'];
        unset($data['roles']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $user->roles()->sync($roles);

        $auditLogger->log('user.updated', $request->user(), $user, ['roles' => $roles]);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $auditLogger->log('user.deleted', $request->user(), $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    /**
     * @return array{name:string,email:string,phone:?string,password:?string,roles:array<int, int>}
     */
    private function validatedData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);
    }
}
