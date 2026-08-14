@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Chat / Session Monitoring</h1>
            <p class="text-sm text-gray-500">Pantau semua sesi pengguna yang aktif di sistem.</p>
        </div>
        <div class="flex space-x-3">
            <form action="{{ route('admin.chats.flush') }}" method="POST" onsubmit="return confirm('Hapus SEMUA sesi? Pengguna akan diminta login ulang.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                    <i class="fa-solid fa-broom mr-1"></i> Flush All Sessions
                </button>
            </form>
            <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold">Dashboard &rarr;</a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.chats.index', 'placeholder' => 'Cari user atau email...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">IP Address</th>
                        <th class="px-6 py-3">User Agent</th>
                        <th class="px-6 py-3">Last Activity</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($sessions as $session)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-800">{{ $session->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $session->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    @if($session->role == 'ADMIN') bg-pink-100 text-pink-800
                                    @elseif($session->role == 'MEMBER') bg-blue-100 text-blue-800
                                    @elseif($session->role == 'MANAGER') bg-purple-100 text-purple-800
                                    @else bg-pink-100 text-pink-900 @endif">
                                    {{ $session->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ $session->ip_address }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate" title="{{ $session->user_agent }}">{{ $session->user_agent }}</td>
                            <td class="px-6 py-4 text-xs text-gray-600">{{ $session->last_activity ? now()->createFromTimestamp($session->last_activity)->diffForHumans() : '-' }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.chats.destroy', $session->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus sesi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete Session</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-gray-500">No active sessions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
