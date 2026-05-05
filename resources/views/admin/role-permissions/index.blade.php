@extends('layouts.admin')

@section('title', 'Role Permissions')

@section('content')
    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Roles</h3>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($roles as $role)
                        <a href="{{ route('admin.role-permissions.index', ['role_id' => $role->id]) }}"
                            @class([
                                'list-group-item list-group-item-action d-flex justify-content-between align-items-center',
                                'active' => $selectedRole?->is($role),
                            ])>
                            <span>{{ $role->label }}</span>
                            <span class="badge {{ $selectedRole?->is($role) ? 'text-bg-light' : 'text-bg-secondary' }}">
                                {{ $role->permissions_count ?? $role->permissions->count() }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            @if ($selectedRole)
                <form method="POST" action="{{ route('admin.role-permissions.update', $selectedRole) }}">
                    @csrf
                    @method('PUT')

                    <div class="card card-outline card-primary">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="card-title mb-0">{{ $selectedRole->label }} Permissions</h3>
                                <p class="text-muted mb-0 small">{{ $selectedRole->name }}</p>
                            </div>
                            @if ($selectedRole->name === 'super_admin')
                                <span class="badge text-bg-success">Always has all permissions</span>
                            @else
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i> Save Permissions
                                </button>
                            @endif
                        </div>

                        <div class="card-body">
                            @if ($selectedRole->name === 'super_admin')
                                <div class="alert alert-info">
                                    Super Admin is locked to every system permission. This satisfies the rule that any system action available to any user is also available to Super Admin.
                                </div>
                            @endif

                            <div class="accordion" id="permissionGroups">
                                @foreach ($permissionGroups as $module => $permissions)
                                    @php
                                        $moduleId = 'permission-module-'.str($module)->slug();
                                        $assignedCount = collect($permissions)->whereIn('id', $selectedPermissionIds)->count();
                                    @endphp
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $moduleId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $moduleId }}">
                                                <span class="fw-semibold">{{ str($module)->replace('_', ' ')->title() }}</span>
                                                <span class="badge text-bg-secondary ms-2">{{ $assignedCount }} / {{ count($permissions) }}</span>
                                            </button>
                                        </h2>
                                        <div id="{{ $moduleId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#permissionGroups">
                                            <div class="accordion-body">
                                                <div class="row g-3">
                                                    @foreach ($permissions as $permission)
                                                        <div class="col-md-6">
                                                            <label class="card h-100 mb-0">
                                                                <span class="card-body">
                                                                    <span class="form-check">
                                                                        <input
                                                                            type="checkbox"
                                                                            class="form-check-input"
                                                                            name="permissions[]"
                                                                            value="{{ $permission->id }}"
                                                                            id="permission-{{ $permission->id }}"
                                                                            @checked($selectedRole->name === 'super_admin' || in_array($permission->id, $selectedPermissionIds, true))
                                                                            @disabled($selectedRole->name === 'super_admin')
                                                                        >
                                                                        <span class="form-check-label">
                                                                            <span class="fw-semibold d-block">{{ $permission->label }}</span>
                                                                            <span class="text-muted small">{{ $permission->name }}</span>
                                                                        </span>
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($selectedRole->name !== 'super_admin')
                            <div class="card-footer d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">Save Permissions</button>
                            </div>
                        @endif
                    </div>
                </form>
            @else
                <div class="alert alert-warning">No roles have been configured yet.</div>
            @endif
        </div>
    </div>
@endsection
