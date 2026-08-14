@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Manager</h1>
            <p class="text-sm text-gray-500">Daftar semua user dengan role <span class="font-semibold text-pink-700">MANAGER</span> (panel /admin/kota) beserta password login.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Back to Dashboard</a>
    </div>

    @include('admin.partials.search', ['route' => 'admin.managers.index', 'placeholder' => 'Cari nama, email, kota, atau provinsi...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Nama</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Password</th>
                    <th class="px-6 py-3">Coverage Kota</th>
                    <th class="px-6 py-3">Provinsi</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($managers as $m)
                    <tr>
                        <td class="px-6 py-4 font-mono text-gray-500">#{{ $m->id }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $m->full_name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $m->email }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-700">{{ $m->password_plain ?? '••••••••' }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">{{ $m->coverage_nama ?: '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">{{ $m->coverage_provinsi ?: '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-900">
                                {{ $m->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($m->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Menghapus user manager ini? Akun tidak akan bisa login ke panel kota lagi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-xs">Current User</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500 text-sm">Tidak ada user dengan role MANAGER.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $managers->links() }}
        </div>
    </div>
</div>
@endsection
