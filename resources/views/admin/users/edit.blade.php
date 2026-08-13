@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit User: {{ $user->full_name }}</h1>
            <p class="text-sm text-gray-500">Perbarui data pengguna (role, status, kontak, password).</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-purple-700 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
                        @foreach(['CUSTOMER','DRIVER','MERCHANT','ADMIN'] as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->role) == $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
                        @foreach(['ACTIVE','INACTIVE','SUSPENDED'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $user->status) == $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400">(kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" name="password" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>

            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endsection
