@extends('layouts.app')

@section('title', 'User Roles')
@section('page_title', 'User Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
<div class="page-header"><h1>Assign Roles to Users</h1><p>Role assignment is limited to roles whose permissions you are authorized to grant.</p></div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Current roles</th><th>Assign roles</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td><span class="fw-semibold">{{ $user->name }}</span><div class="small text-muted">{{ $user->email }}</div></td>
                    <td>{{ $user->roles->pluck('display_name')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', ') ?: 'None' }}</td>
                    <td>
                        @can('users.assign-roles')
                        <form method="POST" action="{{ route('dashboard.users.roles.sync', $user) }}" class="d-flex gap-2 align-items-center">
                            @csrf @method('PUT')
                            <input type="hidden" name="roles_present" value="1">
                            <select class="form-select form-select-sm" name="roles[]" multiple size="3" aria-label="Roles for {{ $user->name }}">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected($user->roles->contains('id', $role->id))>{{ $role->display_name ?: $role->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </form>
                        @else
                            <span class="text-muted">Not permitted</span>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
