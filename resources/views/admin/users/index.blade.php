@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
            <p class="text-sm text-gray-500">Manage all registered customers, drivers, merchants, and admins.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Back to Dashboard</a>
    </div>

    @include('admin.partials.search', ['route' => 'admin.users.index', 'placeholder' => 'Cari nama, email, atau role...'])

    {{-- Sorting / filter role setelah search --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide mr-1">Filter Role:</span>
        @php $roleFilters = [['k'=>'','l'=>'Semua'], ['k'=>'ADMIN','l'=>'Admin'], ['k'=>'MANAGER','l'=>'Manager'], ['k'=>'MEMBER','l'=>'Member']]; @endphp
        @foreach($roleFilters as $rf)
            @php $active = ($role ?? '') === $rf['k']; @endphp
            <a href="{{ route('admin.users.index', array_filter(array_merge(request()->query(), ['role' => $rf['k'] ? $rf['k'] : null]))) }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $active ? 'bg-pink-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $rf['l'] }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Full Name</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Phone</th>
                    <th class="px-6 py-3">Password</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($users as $user)
                    <tr>
                        <td class="px-6 py-4 font-mono text-gray-500">#{{ $user->id }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $user->full_name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-700">{{ $user->password_plain ?? '••••••••' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                @if($user->role == 'ADMIN') bg-pink-100 text-pink-800 
                                @elseif($user->role == 'MEMBER') bg-blue-100 text-blue-800 
                                @elseif($user->role == 'MANAGER') bg-purple-100 text-purple-800 
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-900">
                                {{ $user->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->id !== auth()->id())
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-pink-700 hover:text-pink-900 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                    </form>
                                </div>
                            @else
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-pink-700 hover:text-pink-900 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                    <span class="text-gray-400 text-xs">Current User</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
