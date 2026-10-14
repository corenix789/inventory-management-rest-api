@extends('layouts.app')

@section('title', 'Roles')
@section('page_title', 'Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div><h1>Roles</h1><p>Manage application roles and their permissions</p></div>
    @can('roles.create')
        <a class="btn-primary-custom text-decoration-none" href="{{ route('dashboard.roles.create') }}"><i class="bi bi-plus-lg"></i> Create Role</a>
    @endcan
</div>

<div class="card">
    <div class="card-header"><h6 class="card-title">Configured roles</h6></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td><span class="fw-semibold">{{ $role->display_name ?: $role->name }}</span><div class="small text-muted">{{ $role->slug }}</div></td>
                    <td>{{ $role->permissions_count ?? $role->permissions->count() }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td><span class="badge {{ $role->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $role->status ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end text-nowrap">
                        @can('roles.view-details') <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.roles.show', $role) }}">View</a> @endcan
                        @can('roles.edit') <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard.roles.edit', $role) }}">Edit</a> @endcan
                        @can('roles.delete')
                            @if($role->slug !== 'admin')
                                <form class="d-inline" method="POST" action="{{ route('dashboard.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No roles have been created.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $roles->links() }}</div>
</div>
@endsection
