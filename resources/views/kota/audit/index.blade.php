@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
            <p class="text-sm text-gray-500">Riwayat perubahan yang dilakukan manager di panel ini</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.audit.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Action / entity / user" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search)
                <a href="{{ route('kota.audit.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Log ({{ $logs->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Action</th>
                        <th class="px-6 py-3">Entity</th>
                        <th class="px-6 py-3">Entity ID</th>
                        <th class="px-6 py-3">Perubahan</th>
                        <th class="px-6 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i:s') : '-' }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->user_name ?? '-' }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">{{ $item->action }}</span></td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->entity_type ?? '-' }}</td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->entity_id ?? '-' }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500 max-w-[320px]">
                                @if($item->after_data)
                                    <details>
                                        <summary class="cursor-pointer text-orange-700">Lihat perubahan</summary>
                                        <div class="mt-1 bg-gray-50 p-2 rounded text-xs overflow-auto max-h-32">
                                            @if($item->before_data)<div class="text-red-700">Before: {{ is_string($item->before_data) ? $item->before_data : json_encode($item->before_data) }}</div>@endif
                                            <div class="text-emerald-700">After: {{ is_string($item->after_data) ? $item->after_data : json_encode($item->after_data) }}</div>
                                        </div>
                                    </details>
                                @else - @endif
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500">Belum ada audit log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
