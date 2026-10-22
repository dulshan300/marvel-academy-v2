<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new #[Layout('layouts::portal')] #[Title('Roles & permissions')] class extends Component
{
    public string $newRoleName = '';

    public string $staffName = '';

    public string $staffEmail = '';

    public string $staffPassword = '';

    public string $staffRole = '';

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::with('permissions')->withCount('users')->orderBy('id')->get();
    }

    /**
     * Users holding the admin role or any custom staff role.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function staff(): Collection
    {
        $portalRoles = [RoleEnum::Teacher->value, RoleEnum::Parent->value, RoleEnum::Student->value];

        return User::whereHas('roles', fn ($query) => $query->whereNotIn('name', $portalRoles))
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    public function togglePermission(int $roleId, string $permission): void
    {
        $this->authorize(PermissionEnum::ManageRoles->value);

        abort_unless(PermissionEnum::tryFrom($permission) !== null, 422);

        $role = Role::findOrFail($roleId);
        abort_if($role->name === RoleEnum::Admin->value, 403, 'Admins always hold every permission.');

        $role->hasPermissionTo($permission) ? $role->revokePermissionTo($permission) : $role->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        unset($this->roles);
    }

    public function createRole(): void
    {
        $this->authorize(PermissionEnum::ManageRoles->value);

        $this->newRoleName = mb_strtolower(trim($this->newRoleName));

        $this->validate([
            'newRoleName' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9 -]*$/', Rule::unique('roles', 'name')],
        ], ['newRoleName.regex' => 'Use lowercase letters, numbers, spaces or hyphens.'], ['newRoleName' => 'role name']);

        Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);

        $this->reset('newRoleName');
        unset($this->roles);
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorize(PermissionEnum::ManageRoles->value);

        $role = Role::withCount('users')->findOrFail($roleId);

        abort_if(RoleEnum::tryFrom($role->name) !== null, 403, 'Portal roles cannot be deleted.');

        if ($role->users_count > 0) {
            $this->addError('roles', "Remove the {$role->name} role from its {$role->users_count} staff first.");

            return;
        }

        $role->delete();
        unset($this->roles);
    }

    public function createStaff(): void
    {
        $this->authorize(PermissionEnum::ManageRoles->value);

        $this->staffEmail = mb_strtolower(trim($this->staffEmail));
        $assignable = $this->roles->pluck('name')->reject(fn ($name) => in_array($name, [RoleEnum::Teacher->value, RoleEnum::Parent->value, RoleEnum::Student->value], true));

        $validated = $this->validate([
            'staffName' => ['required', 'string', 'max:120'],
            'staffEmail' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'staffPassword' => ['required', 'string', Password::defaults()],
            'staffRole' => ['required', Rule::in($assignable->all())],
        ], attributes: ['staffName' => 'name', 'staffEmail' => 'email', 'staffPassword' => 'password', 'staffRole' => 'role']);

        $user = User::create([
            'name' => $validated['staffName'],
            'email' => $validated['staffEmail'],
            'password' => $validated['staffPassword'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($validated['staffRole']);

        $this->reset('staffName', 'staffEmail', 'staffPassword', 'staffRole');
        unset($this->staff, $this->roles);
    }

    public function removeStaffRole(int $userId, string $roleName): void
    {
        $this->authorize(PermissionEnum::ManageRoles->value);

        abort_if($userId === auth()->id() && $roleName === RoleEnum::Admin->value, 403, 'You cannot remove your own admin role.');

        User::findOrFail($userId)->removeRole($roleName);
        unset($this->staff, $this->roles);
    }
};
?>

<div class="space-y-8">
    <x-portal.card title="Permission matrix">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="pb-3">Permission</th>
                        @foreach ($this->roles as $role)
                            <th class="px-3 pb-3 text-center whitespace-nowrap">
                                {{ Str::headline($role->name) }}
                                <span class="block font-normal normal-case">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</span>
                                @if (\App\Enums\Role::tryFrom($role->name) === null)
                                    <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="Delete the {{ $role->name }} role?" class="font-normal text-red-600 normal-case">Delete</button>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (\App\Enums\Permission::cases() as $permission)
                        <tr wire:key="permission-{{ $permission->value }}">
                            <td class="py-2.5 font-medium text-slate-700">{{ $permission->label() }}</td>
                            @foreach ($this->roles as $role)
                                @php $isAdmin = $role->name === \App\Enums\Role::Admin->value; @endphp
                                <td class="px-3 py-2.5 text-center">
                                    <input
                                        type="checkbox"
                                        @checked($isAdmin || $role->permissions->contains('name', $permission->value))
                                        @disabled($isAdmin)
                                        wire:click="togglePermission({{ $role->id }}, '{{ $permission->value }}')"
                                        class="size-4 rounded border-slate-300 text-brand-700 disabled:opacity-60"
                                        aria-label="{{ $permission->label() }} for {{ $role->name }}"
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('roles') <p class="form-error">{{ $message }}</p> @enderror

        <form wire:submit="createRole" class="mt-6 flex max-w-md gap-2 border-t border-slate-100 pt-6">
            <input type="text" wire:model="newRoleName" placeholder="New staff role, e.g. course editor" class="form-input">
            <button type="submit" class="btn-secondary shrink-0">Add role</button>
        </form>
        @error('newRoleName') <p class="form-error">{{ $message }}</p> @enderror
    </x-portal.card>

    <div class="grid gap-8 xl:grid-cols-3">
        <x-portal.card title="Staff accounts" class="xl:col-span-2">
            <ul class="divide-y divide-slate-100">
                @foreach ($this->staff as $member)
                    <li wire:key="staff-{{ $member->id }}" class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $member->name }}</p>
                            <p class="text-xs text-slate-500">{{ $member->email }}</p>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            @foreach ($member->roles as $role)
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200">
                                    {{ Str::headline($role->name) }}
                                    @unless ($member->id === auth()->id() && $role->name === \App\Enums\Role::Admin->value)
                                        <button type="button" wire:click="removeStaffRole({{ $member->id }}, '{{ $role->name }}')" wire:confirm="Remove {{ $role->name }} from {{ $member->name }}?" class="text-brand-500 hover:text-red-600" aria-label="Remove role">×</button>
                                    @endunless
                                </span>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-portal.card>

        <x-portal.card title="Add staff member">
            <form wire:submit="createStaff" class="space-y-3" novalidate>
                <input type="text" wire:model="staffName" placeholder="Full name" class="form-input">
                @error('staffName') <p class="form-error">{{ $message }}</p> @enderror
                <input type="email" wire:model="staffEmail" placeholder="Email" class="form-input">
                @error('staffEmail') <p class="form-error">{{ $message }}</p> @enderror
                <input type="password" wire:model="staffPassword" placeholder="Temporary password" autocomplete="new-password" class="form-input">
                @error('staffPassword') <p class="form-error">{{ $message }}</p> @enderror
                <select wire:model="staffRole" class="form-input" aria-label="Role">
                    <option value="">Choose a role</option>
                    @foreach ($this->roles as $role)
                        @continue(in_array($role->name, ['teacher', 'parent', 'student'], true))
                        <option value="{{ $role->name }}">{{ Str::headline($role->name) }}</option>
                    @endforeach
                </select>
                @error('staffRole') <p class="form-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-secondary w-full">Create staff account</button>
                <p class="text-xs text-slate-500">Teachers are added from the Teachers page.</p>
            </form>
        </x-portal.card>
    </div>
</div>
