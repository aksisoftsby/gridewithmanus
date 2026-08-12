@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Driver Management</h1>
            <p class="text-sm text-gray-500">Kelola kurir / driver yang terdaftar di sistem.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.drivers.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Register Driver
            </a>
            <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:underline text-sm font-semibold">Dashboard &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Driver</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Verified</th>
                        <th class="px-6 py-3">Rating</th>
                        <th class="px-6 py-3">Trips</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Joined</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($drivers as $driver)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-800">{{ $driver->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $driver->email }} @if($driver->phone) | {{ $driver->phone }} @endif</div>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.drivers.status', $driver->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="text-xs font-semibold rounded-full px-2.5 py-1 border-0 focus:ring-2 focus:ring-emerald-500 cursor-pointer
                                        {{ $driver->status == 'ONLINE' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                        <option value="ONLINE" @selected($driver->status == 'ONLINE')>ONLINE</option>
                                        <option value="OFFLINE" @selected($driver->status == 'OFFLINE')>OFFLINE</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                @if($driver->is_verified)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Verified</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold">★ {{ number_format($driver->rating, 2) }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $driver->total_trips }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500 font-mono">{{ $driver->current_lat }}, {{ $driver->current_lng }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ $driver->created_at ? \Carbon\Carbon::parse($driver->created_at)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove this driver?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-6 text-center text-gray-500">No drivers registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $drivers->links() }}
        </div>
    </div>
</div>
@endsection
