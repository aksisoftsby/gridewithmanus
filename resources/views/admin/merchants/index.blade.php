@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Merchant Management</h1>
            <p class="text-sm text-gray-500">Manage partner restaurants, marts, and retail shops.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold self-center">&larr; Dashboard</a>
            <a href="{{ route('admin.merchants.create') }}" class="bg-pink-700 hover:bg-pink-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Merchant
            </a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.merchants.index', 'placeholder' => 'Cari merchant, kota, atau owner...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Merchant Name</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Owner</th>
                    <th class="px-6 py-3">City</th>
                    <th class="px-6 py-3">Location</th>
                    <th class="px-6 py-3">Rating</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($merchants as $merchant)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $merchant->name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-900">
                                {{ $merchant->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $merchant->owner_name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $merchant->city }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            @if($merchant->latitude && $merchant->longitude)
                                <a href="https://www.google.com/maps?q={{ $merchant->latitude }},{{ $merchant->longitude }}" target="_blank" class="text-pink-700 hover:underline">{{ number_format((float)$merchant->latitude, 4) }}, {{ number_format((float)$merchant->longitude, 4) }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-blue-600"><i class="fa-solid fa-star text-xs mr-1"></i>{{ $merchant->rating }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">
                                {{ $merchant->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.merchants.edit', $merchant->id) }}" class="text-pink-700 hover:text-pink-900 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                <form action="{{ route('admin.merchants.destroy', $merchant->id) }}" method="POST" onsubmit="return confirm('Delete this merchant?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-gray-500">No merchants found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $merchants->links() }}
        </div>
    </div>
</div>
@endsection
