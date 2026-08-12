@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Register New Driver</h1>
            <p class="text-sm text-gray-500">Daftarkan user sebagai kurir / driver.</p>
        </div>
        <a href="{{ route('admin.drivers.index') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Back to Drivers</a>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
        <form action="{{ route('admin.drivers.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User (Pilih akun)</label>
                    <select name="user_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">-- Pilih User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Initial Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="ONLINE">ONLINE</option>
                        <option value="OFFLINE">OFFLINE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating Awal</label>
                    <input type="number" step="0.01" min="0" max="5" name="rating" value="5.00" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.drivers.index') }}" class="px-4 py-2 border rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow transition">Register Driver</button>
            </div>
        </form>
    </div>
</div>
@endsection
