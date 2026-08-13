@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Pengguna Panel Kota</h1>
            <p class="text-sm text-gray-500">Kelola role panel kota (ADMIN / MANAGER / MEMBER) pengguna</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-purple-50 border border-purple-200 text-purple-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <!-- Search -->
    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.users.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari (email / nama / phone)</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="cth: admin@superapp.com" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none w-72">
            </div>
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search)
                <a href="{{ route('kota.users.index') }}" class="text-sm text-amber-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Pengguna ({{ $users->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Phone</th>
                        <th class="px-6 py-3">Role Sistem</th>
                        <th class="px-6 py-3">Role Kota</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-500">{{ $u->id }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $u->full_name }}</td>
                            <td class="px-6 py-3">{{ $u->email }}</td>
                            <td class="px-6 py-3">{{ $u->phone }}</td>
                            <td class="px-6 py-3"><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">{{ $u->role }}</span></td>
                            <td class="px-6 py-3">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $u->role_kota === 'ADMIN' ? 'bg-red-100 text-red-800' : ($u->role_kota === 'MANAGER' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">{{ $u->role_kota }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if(auth()->user()->role === 'ADMIN')
                                <form action="{{ route('kota.users.role.update', $u->id) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role_kota" class="px-2 py-1 border rounded text-xs">
                                        <option value="MEMBER" {{ $u->role_kota === 'MEMBER' ? 'selected' : '' }}>MEMBER</option>
                                        <option value="MANAGER" {{ $u->role_kota === 'MANAGER' ? 'selected' : '' }}>MANAGER</option>
                                        <option value="ADMIN" {{ $u->role_kota === 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                                    </select>
                                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded text-xs font-semibold">Simpan</button>
                                </form>
                                @else
                                <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-6 text-center text-gray-500">Tidak ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
