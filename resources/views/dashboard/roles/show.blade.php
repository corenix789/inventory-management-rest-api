@extends('layouts.app')

@section('title', 'Role Details')
@section('page_title', 'Role Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">{{ $role->display_name ?: $role->name }}</li>
@endsection

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div><h1>{{ $role->display_name ?: $role->name }}</h1><p>{{ $role->description ?: $role->slug }}</p></div>
    @can('roles.edit')<a href="{{ route('dashboard.roles.edit', $role) }}" class="btn-primary-custom text-decoration-none">Edit role</a>@endcan
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="card-title">Permissions ({{ $role->permissions->count() }})</h6></div>
            <div class="card-body">
                @forelse($role->permissions->groupBy('module') as $module => $permissions)
                    <div class="mb-3"><strong class="text-capitalize">{{ str_replace('-', ' ', $module) }}</strong>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($permissions as $permission)<span class="badge text-bg-light border">{{ $permission->display_name ?: $permission->name }}</span>@endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">This role has no permissions.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="card-title">Assigned users ({{ $role->users->count() }})</h6></div>
            <div class="list-group list-group-flush">
                @forelse($role->users as $user)
                    <div class="list-group-item"><div class="fw-semibold">{{ $user->name }}</div><div class="small text-muted">{{ $user->email }}</div></div>
                @empty
                    <div class="list-group-item text-muted">No users have this role.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
