@csrf
<div class="card-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Check the form and try again.</strong>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="phone" class="form-label">Phone</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="roles" class="form-label">Roles</label>
            <select id="roles" name="roles[]" class="form-select @error('roles') is-invalid @enderror" multiple required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', $selectedRoles), true))>{{ $role->label }}</option>
                @endforeach
            </select>
            @error('roles')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if (! $user->exists) required @endif>
            @if ($user->exists)<div class="form-text">Leave blank to keep the current password.</div>@endif
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" @if (! $user->exists) required @endif>
        </div>
    </div>
</div>
<div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-success">{{ $submitLabel }}</button>
</div>
