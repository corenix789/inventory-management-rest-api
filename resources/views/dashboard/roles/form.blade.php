@extends('layouts.app')

@section('title', $role->exists ? 'Edit Role' : 'Create Role')
@section('page_title', $role->exists ? 'Edit Role' : 'Create Role')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">{{ $role->exists ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
<div class="page-header"><h1>{{ $role->exists ? 'Edit Role' : 'Create Role' }}</h1><p>Set role details and choose the permissions it grants.</p></div>
<form method="POST" action="{{ $role->exists ? route('dashboard.roles.update', $role) : route('dashboard.roles.store') }}">
    @csrf
    @if($role->exists) @method('PUT') @endif
    <div class="card mb-4">
        <div class="card-header"><h6 class="card-title">Role details</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" class="form-control" name="name" value="{{ old('name', $role->name) }}" required maxlength="100">
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" class="form-control" name="slug" value="{{ old('slug', $role->slug) }}" maxlength="100">
                    @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="display_name">Display name</label>
                    <input id="display_name" class="form-control" name="display_name" value="{{ old('display_name', $role->display_name) }}" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-select" name="status" required>
                        <option value="1" @selected((string) old('status', (int) ($role->status ?? true)) === '1')>Active</option>
                        <option value="0" @selected((string) old('status', (int) ($role->status ?? true)) === '0')>Inactive</option>
                    </select>
                    @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" class="form-control" name="description" rows="2" maxlength="500">{{ old('description', $role->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    @can('roles.assign-permissions')
    <input type="hidden" name="permissions_present" value="1">
    @php($selectedPermissions = collect(old('permissions', $role->exists ? $role->permissions->pluck('id')->all() : [])))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Permissions</h6>
            <label class="form-check mb-0"><input class="form-check-input" type="checkbox" id="selectAllPermissions"><span class="form-check-label">Select all permissions</span></label>
        </div>
        @error('permissions')<div class="alert alert-danger m-3">{{ $message }}</div>@enderror
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead><tr><th>Module</th><th>Available permissions</th><th>Module controls</th></tr></thead>
                <tbody>
                @foreach($permissions as $module => $modulePermissions)
                    <tr>
                        <th class="text-capitalize">{{ str_replace('-', ' ', $module) }}</th>
                        <td>
                            <div class="d-flex flex-wrap gap-3">
                            @foreach($modulePermissions as $permission)
                                <label class="form-check">
                                    <input class="form-check-input permission-checkbox module-{{ \Illuminate\Support\Str::slug($module) }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($selectedPermissions->contains($permission->id))>
                                    <span class="form-check-label">{{ $permission->display_name ?: $permission->name }}</span>
                                </label>
                            @endforeach
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="setModulePermissions('{{ \Illuminate\Support\Str::slug($module) }}', true)">Select all</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setModulePermissions('{{ \Illuminate\Support\Str::slug($module) }}', false)">Clear</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endcan

    <div class="d-flex gap-2">
        <button class="btn-primary-custom" type="submit">Save changes</button>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.roles.index') }}">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
function setModulePermissions(module, checked) {
    document.querySelectorAll('.module-' + module).forEach(input => input.checked = checked);
}
document.getElementById('selectAllPermissions')?.addEventListener('change', event => {
    document.querySelectorAll('.permission-checkbox').forEach(input => input.checked = event.target.checked);
});
</script>
@endpush
